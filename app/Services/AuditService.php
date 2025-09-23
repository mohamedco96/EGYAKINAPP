<?php

namespace App\Services;

use App\Jobs\ProcessAuditLog;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * Log a model event (created, updated, deleted).
     */
    public function logModelEvent(string $eventType, Model $model, array $oldValues = [], array $newValues = []): ?AuditLog
    {
        try {
            $user = Auth::user();
            $request = request();

            // Determine what attributes changed
            $changedAttributes = [];
            if ($eventType === 'updated' && $model->wasChanged()) {
                $changedAttributes = array_keys($model->getChanges());

                // Get old values for changed attributes only
                if (empty($oldValues)) {
                    $oldValues = [];
                    foreach ($changedAttributes as $attribute) {
                        $oldValues[$attribute] = $model->getOriginal($attribute);
                    }
                }

                // Get new values for changed attributes only
                if (empty($newValues)) {
                    $newValues = [];
                    foreach ($changedAttributes as $attribute) {
                        $newValues[$attribute] = $model->getAttribute($attribute);
                    }
                }
            }

            // Filter out sensitive data
            $filteredOldValues = $this->filterSensitiveData($oldValues);
            $filteredNewValues = $this->filterSensitiveData($newValues);
            $filteredChangedAttributes = array_diff($changedAttributes, $this->getSensitiveFields());

            $auditData = [
                'event_type' => $eventType,
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'user_id' => $user?->id,
                'user_type' => $user ? get_class($user) : null,
                'user_name' => $user?->name,
                'user_email' => $user?->email,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'request_data' => $this->getFilteredRequestData($request),
                'old_values' => $filteredOldValues,
                'new_values' => $filteredNewValues,
                'changed_attributes' => $filteredChangedAttributes,
                'session_id' => $this->getSessionId($request),
                'device_type' => $this->detectDeviceType($request),
                'platform' => $this->detectPlatform($request),
                'performed_at' => now(),
            ];

            // Check if async processing is enabled
            if (config('audit.performance.async_processing', true)) {
                ProcessAuditLog::dispatch($auditData);

                return null; // Return null for async processing
            } else {
                return AuditLog::create($auditData);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create audit log', [
                'event_type' => $eventType,
                'model_class' => get_class($model),
                'model_id' => $model->getKey(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Log an authentication event.
     */
    public function logAuthEvent(string $eventType, ?User $user = null, array $metadata = []): ?AuditLog
    {
        try {
            $request = request();
            $currentUser = $user ?? Auth::user();

            $auditData = [
                'event_type' => $eventType,
                'user_id' => $currentUser?->id,
                'user_type' => $currentUser ? get_class($currentUser) : null,
                'user_name' => $currentUser?->name,
                'user_email' => $currentUser?->email ?? $metadata['email'] ?? null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'request_data' => $this->getFilteredRequestData($request),
                'metadata' => $metadata,
                'session_id' => $this->getSessionId($request),
                'device_type' => $this->detectDeviceType($request),
                'platform' => $this->detectPlatform($request),
                'performed_at' => now(),
            ];

            // Check if async processing is enabled
            if (config('audit.performance.async_processing', true)) {
                ProcessAuditLog::dispatch($auditData);

                return null;
            } else {
                return AuditLog::create($auditData);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create auth audit log', [
                'event_type' => $eventType,
                'user_id' => $currentUser?->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Log a custom event.
     */
    public function logCustomEvent(
        string $eventType,
        ?string $description = null,
        array $metadata = [],
        ?Model $relatedModel = null,
        ?User $user = null
    ): ?AuditLog {
        try {
            $request = request();
            $currentUser = $user ?? Auth::user();

            $auditData = [
                'event_type' => $eventType,
                'auditable_type' => $relatedModel ? get_class($relatedModel) : null,
                'auditable_id' => $relatedModel?->getKey(),
                'user_id' => $currentUser?->id,
                'user_type' => $currentUser ? get_class($currentUser) : null,
                'user_name' => $currentUser?->name,
                'user_email' => $currentUser?->email,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'request_data' => $this->getFilteredRequestData($request),
                'description' => $description,
                'metadata' => $metadata,
                'session_id' => $this->getSessionId($request),
                'device_type' => $this->detectDeviceType($request),
                'platform' => $this->detectPlatform($request),
                'performed_at' => now(),
            ];

            // Check if async processing is enabled
            if (config('audit.performance.async_processing', true)) {
                ProcessAuditLog::dispatch($auditData);

                return null;
            } else {
                return AuditLog::create($auditData);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create custom audit log', [
                'event_type' => $eventType,
                'description' => $description,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Log an API request.
     */
    public function logApiRequest(Request $request, $response = null, ?User $user = null): ?AuditLog
    {
        try {
            $currentUser = $user ?? Auth::user();

            $metadata = [
                'response_status' => $response?->getStatusCode(),
                'response_size' => $response ? strlen($response->getContent()) : null,
                'execution_time' => $this->getExecutionTime($request),
            ];

            $auditData = [
                'event_type' => 'api_request',
                'user_id' => $currentUser?->id,
                'user_type' => $currentUser ? get_class($currentUser) : null,
                'user_name' => $currentUser?->name,
                'user_email' => $currentUser?->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'request_data' => $this->getFilteredRequestData($request),
                'metadata' => $metadata,
                'session_id' => $request->session()?->getId(),
                'device_type' => $this->detectDeviceType($request),
                'platform' => $this->detectPlatform($request),
                'performed_at' => now(),
            ];

            // Check if async processing is enabled
            if (config('audit.performance.async_processing', true)) {
                ProcessAuditLog::dispatch($auditData);

                return null;
            } else {
                return AuditLog::create($auditData);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create API audit log', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get filtered request data (remove sensitive information).
     */
    protected function getFilteredRequestData(?Request $request): array
    {
        if (! $request) {
            return [];
        }

        $data = $request->all();

        // Handle file uploads - convert to serializable format
        $data = $this->processFileUploads($data);

        return $this->filterSensitiveData($data);
    }

    /**
     * Process file uploads to make them serializable.
     */
    protected function processFileUploads(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                // Convert UploadedFile to serializable array
                $data[$key] = [
                    '_file_info' => [
                        'original_name' => $value->getClientOriginalName(),
                        'mime_type' => $value->getClientMimeType(),
                        'size' => $value->getSize(),
                        'extension' => $value->getClientOriginalExtension(),
                        'is_valid' => $value->isValid(),
                        'error' => $value->getError(),
                    ],
                ];
            } elseif (is_array($value)) {
                // Recursively process nested arrays
                $data[$key] = $this->processFileUploads($value);
            }
        }

        return $data;
    }

    /**
     * Filter sensitive data from an array.
     */
    protected function filterSensitiveData(array $data): array
    {
        $sensitiveFields = $this->getSensitiveFields();

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[FILTERED]';
            }
        }

        return $data;
    }

    /**
     * Get list of sensitive fields that should be filtered.
     */
    protected function getSensitiveFields(): array
    {
        return [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'api_token',
            'access_token',
            'refresh_token',
            'secret',
            'api_key',
            'private_key',
            'credit_card',
            'cvv',
            'ssn',
            'social_security_number',
        ];
    }

    /**
     * Detect device type from request.
     */
    protected function detectDeviceType(?Request $request): ?string
    {
        if (! $request) {
            return null;
        }

        $userAgent = $request->userAgent();

        if (Str::contains($userAgent, ['Mobile', 'Android', 'iPhone', 'iPad'])) {
            return 'mobile';
        }

        if (Str::contains($userAgent, ['Postman', 'curl', 'HTTPie', 'Insomnia'])) {
            return 'api';
        }

        return 'web';
    }

    /**
     * Detect platform from request.
     */
    protected function detectPlatform(?Request $request): ?string
    {
        if (! $request) {
            return null;
        }

        $userAgent = $request->userAgent();

        if (Str::contains($userAgent, 'iPhone') || Str::contains($userAgent, 'iPad')) {
            return 'iOS';
        }

        if (Str::contains($userAgent, 'Android')) {
            return 'Android';
        }

        if (Str::contains($userAgent, ['Windows', 'Macintosh', 'Linux'])) {
            return 'Desktop';
        }

        return 'Unknown';
    }

    /**
     * Get execution time for the request.
     */
    protected function getExecutionTime(Request $request): ?float
    {
        if (defined('LARAVEL_START')) {
            return microtime(true) - LARAVEL_START;
        }

        return null;
    }

    /**
     * Safely get session ID from request.
     */
    protected function getSessionId(?Request $request): ?string
    {
        if (! $request) {
            return null;
        }

        try {
            // Check if session is available and started
            if ($request->hasSession() && $request->session()->isStarted()) {
                return $request->session()->getId();
            }
        } catch (\Exception $e) {
            // Session not available or not started, return null
            Log::debug('Session not available for audit logging', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl(),
            ]);
        }

        return null;
    }

    /**
     * Get audit logs for a specific model.
     */
    public function getModelAuditLogs(Model $model, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::forModel(get_class($model), $model->getKey())
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs for a specific user.
     */
    public function getUserAuditLogs(User $user, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::byUser($user->id)
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Clean up old audit logs.
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        return AuditLog::where('performed_at', '<', $cutoffDate)->delete();
    }

    /**
     * Get audit statistics.
     */
    public function getAuditStats(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $totalLogs = AuditLog::where('performed_at', '>=', $startDate)->count();

        $eventTypes = AuditLog::where('performed_at', '>=', $startDate)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'event_type')
            ->toArray();

        $topUsers = AuditLog::where('performed_at', '>=', $startDate)
            ->whereNotNull('user_id')
            ->selectRaw('user_name, user_email, COUNT(*) as count')
            ->groupBy('user_name', 'user_email')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        return [
            'total_logs' => $totalLogs,
            'event_types' => $eventTypes,
            'top_users' => $topUsers,
            'period_days' => $days,
        ];
    }
}
