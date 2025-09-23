<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip auditing for certain routes/conditions
        if ($this->shouldSkipAudit($request)) {
            return $next($request);
        }

        // Store start time for performance tracking
        $startTime = microtime(true);

        // Process the request
        $response = $next($request);

        // Log the request after processing (in background to avoid performance impact)
        $this->logRequestAsync($request, $response, $startTime);

        return $response;
    }

    /**
     * Determine if the request should be skipped from auditing.
     */
    protected function shouldSkipAudit(Request $request): bool
    {
        // Get skip routes from configuration
        $skipRoutes = config('audit.http.skip_routes', [
            'telescope*',
            'horizon*',
            '_debugbar*',
            'livewire*',
            'health*',
            'ping*',
            'status*',
        ]);

        foreach ($skipRoutes as $pattern) {
            if (Str::is($pattern, $request->path())) {
                return true;
            }
        }

        // Skip static assets
        if (Str::contains($request->path(), ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2'])) {
            return true;
        }

        // Skip GET requests to certain paths (to reduce noise)
        if ($request->isMethod('GET') && $this->isReadOnlyRoute($request)) {
            return true;
        }

        // Skip if this is a console/artisan command
        if (app()->runningInConsole()) {
            return true;
        }

        return false;
    }

    /**
     * Check if the route is read-only and should be skipped for GET requests.
     */
    protected function isReadOnlyRoute(Request $request): bool
    {
        $readOnlyPatterns = [
            'api/*/index',
            'api/*/show',
            'filament/assets/*',
        ];

        foreach ($readOnlyPatterns as $pattern) {
            if (Str::is($pattern, $request->path())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log the request asynchronously to avoid performance impact.
     */
    protected function logRequestAsync(Request $request, Response $response, float $startTime): void
    {
        // Extract serializable data from request and response
        $requestData = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'input' => $request->all(),
            'route_name' => $request->route()?->getName(),
            'route_action' => $request->route()?->getActionName(),
        ];

        $responseData = [
            'status_code' => $response->getStatusCode(),
            'content_length' => strlen($response->getContent()),
            'headers' => $response->headers->all(),
        ];

        $executionTime = microtime(true) - $startTime;
        $eventType = $this->determineEventType($request, $response);
        $description = $this->generateDescription($request, $response);

        // Create audit data for the job
        $user = Auth::user();
        $auditData = [
            'event_type' => $eventType,
            'user_id' => $user?->id,
            'user_type' => $user ? get_class($user) : null,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'ip_address' => $requestData['ip'],
            'user_agent' => $requestData['user_agent'],
            'url' => $requestData['url'],
            'method' => $requestData['method'],
            'request_data' => $this->filterSensitiveData($requestData['input']),
            'description' => $description,
            'metadata' => [
                'response_status' => $responseData['status_code'],
                'execution_time' => $executionTime,
                'response_size' => $responseData['content_length'],
                'route_name' => $requestData['route_name'],
                'route_action' => $requestData['route_action'],
            ],
            'session_id' => $this->getSessionId($request),
            'device_type' => $this->detectDeviceType($request),
            'platform' => $this->detectPlatform($request),
            'performed_at' => now(),
        ];

        // Dispatch the audit job
        \App\Jobs\ProcessAuditLog::dispatch($auditData);
    }

    /**
     * Filter sensitive data from request input.
     */
    protected function filterSensitiveData(array $data): array
    {
        // First process file uploads
        $data = $this->processFileUploads($data);

        $sensitiveFields = [
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
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[FILTERED]';
            }
        }

        return $data;
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
     * Determine the event type based on the request.
     */
    protected function determineEventType(Request $request, Response $response): string
    {
        $method = $request->method();
        $statusCode = $response->getStatusCode();

        // Handle authentication routes
        if (Str::contains($request->path(), ['login', 'auth'])) {
            return $statusCode >= 200 && $statusCode < 300 ? 'login' : 'failed_login';
        }

        if (Str::contains($request->path(), 'logout')) {
            return 'logout';
        }

        // Handle CRUD operations
        return match ($method) {
            'POST' => 'http_create',
            'PUT', 'PATCH' => 'http_update',
            'DELETE' => 'http_delete',
            'GET' => 'http_read',
            default => 'http_request'
        };
    }

    /**
     * Generate a human-readable description for the request.
     */
    protected function generateDescription(Request $request, Response $response): string
    {
        $user = Auth::user();
        $userName = $user?->name ?? 'Guest';
        $method = $request->method();
        $path = $request->path();
        $statusCode = $response->getStatusCode();

        $action = match ($method) {
            'POST' => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            'GET' => 'accessed',
            default => 'requested'
        };

        return "{$userName} {$action} {$path} (Status: {$statusCode})";
    }

    /**
     * Detect device type from request.
     */
    protected function detectDeviceType(Request $request): ?string
    {
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
    protected function detectPlatform(Request $request): ?string
    {
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
     * Safely get session ID from request.
     */
    protected function getSessionId(Request $request): ?string
    {
        try {
            // Check if session is available and started
            if ($request->hasSession() && $request->session()->isStarted()) {
                return $request->session()->getId();
            }
        } catch (\Exception $e) {
            // Session not available or not started, return null
            \Log::debug('Session not available for audit logging', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl(),
            ]);
        }

        return null;
    }
}
