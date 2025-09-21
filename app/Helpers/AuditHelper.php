<?php

namespace App\Helpers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditHelper
{
    /**
     * Quick audit log creation.
     */
    public static function log(string $eventType, ?string $description = null, array $metadata = [], ?Model $relatedModel = null): ?AuditLog
    {
        $auditService = app(AuditService::class);

        return $auditService->logCustomEvent($eventType, $description, $metadata, $relatedModel);
    }

    /**
     * Log authentication events.
     */
    public static function logAuth(string $eventType, ?User $user = null, array $metadata = []): ?AuditLog
    {
        $auditService = app(AuditService::class);

        return $auditService->logAuthEvent($eventType, $user, $metadata);
    }

    /**
     * Log user login.
     */
    public static function logLogin(?User $user = null, array $metadata = []): ?AuditLog
    {
        return self::logAuth('login', $user, $metadata);
    }

    /**
     * Log user logout.
     */
    public static function logLogout(?User $user = null, array $metadata = []): ?AuditLog
    {
        return self::logAuth('logout', $user, $metadata);
    }

    /**
     * Log failed login attempt.
     */
    public static function logFailedLogin(string $email, array $metadata = []): ?AuditLog
    {
        $metadata['email'] = $email;

        return self::logAuth('failed_login', null, $metadata);
    }

    /**
     * Log password reset.
     */
    public static function logPasswordReset(?User $user = null, array $metadata = []): ?AuditLog
    {
        return self::logAuth('password_reset', $user, $metadata);
    }

    /**
     * Log email verification.
     */
    public static function logEmailVerified(?User $user = null, array $metadata = []): ?AuditLog
    {
        return self::logAuth('email_verified', $user, $metadata);
    }

    /**
     * Log file operations.
     */
    public static function logFileOperation(string $operation, string $filename, array $metadata = []): ?AuditLog
    {
        $metadata['filename'] = $filename;
        $metadata['operation'] = $operation;

        return self::log("file_{$operation}", "File {$operation}: {$filename}", $metadata);
    }

    /**
     * Log file upload.
     */
    public static function logFileUpload(string $filename, array $metadata = []): ?AuditLog
    {
        return self::logFileOperation('upload', $filename, $metadata);
    }

    /**
     * Log file download.
     */
    public static function logFileDownload(string $filename, array $metadata = []): ?AuditLog
    {
        return self::logFileOperation('download', $filename, $metadata);
    }

    /**
     * Log file deletion.
     */
    public static function logFileDelete(string $filename, array $metadata = []): ?AuditLog
    {
        return self::logFileOperation('delete', $filename, $metadata);
    }

    /**
     * Log security events.
     */
    public static function logSecurityEvent(string $eventType, string $description, array $metadata = []): ?AuditLog
    {
        $metadata['security_event'] = true;

        return self::log("security_{$eventType}", $description, $metadata);
    }

    /**
     * Log permission changes.
     */
    public static function logPermissionChange(User $user, string $permission, string $action, array $metadata = []): ?AuditLog
    {
        $description = "Permission '{$permission}' {$action} for user {$user->name}";
        $metadata['permission'] = $permission;
        $metadata['action'] = $action;
        $metadata['target_user_id'] = $user->id;

        return self::log('permission_change', $description, $metadata, $user);
    }

    /**
     * Log role changes.
     */
    public static function logRoleChange(User $user, string $role, string $action, array $metadata = []): ?AuditLog
    {
        $description = "Role '{$role}' {$action} for user {$user->name}";
        $metadata['role'] = $role;
        $metadata['action'] = $action;
        $metadata['target_user_id'] = $user->id;

        return self::log('role_change', $description, $metadata, $user);
    }

    /**
     * Log data export events.
     */
    public static function logDataExport(string $dataType, array $metadata = []): ?AuditLog
    {
        $description = "Data export: {$dataType}";
        $metadata['data_type'] = $dataType;
        $metadata['export_time'] = now();

        return self::log('data_export', $description, $metadata);
    }

    /**
     * Log data import events.
     */
    public static function logDataImport(string $dataType, int $recordCount, array $metadata = []): ?AuditLog
    {
        $description = "Data import: {$dataType} ({$recordCount} records)";
        $metadata['data_type'] = $dataType;
        $metadata['record_count'] = $recordCount;
        $metadata['import_time'] = now();

        return self::log('data_import', $description, $metadata);
    }

    /**
     * Log system configuration changes.
     */
    public static function logConfigChange(string $configKey, $oldValue, $newValue, array $metadata = []): ?AuditLog
    {
        $description = "Configuration changed: {$configKey}";
        $metadata['config_key'] = $configKey;
        $metadata['old_value'] = $oldValue;
        $metadata['new_value'] = $newValue;

        return self::log('config_change', $description, $metadata);
    }

    /**
     * Log API usage.
     */
    public static function logApiUsage(string $endpoint, string $method, array $metadata = []): ?AuditLog
    {
        $description = "API usage: {$method} {$endpoint}";
        $metadata['endpoint'] = $endpoint;
        $metadata['method'] = $method;

        return self::log('api_usage', $description, $metadata);
    }

    /**
     * Log suspicious activity.
     */
    public static function logSuspiciousActivity(string $activity, array $metadata = []): ?AuditLog
    {
        $metadata['suspicious'] = true;
        $metadata['requires_review'] = true;

        return self::logSecurityEvent('suspicious_activity', $activity, $metadata);
    }

    /**
     * Get audit logs for current user.
     */
    public static function getCurrentUserLogs(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return AuditLog::byUser($user->id)
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent audit logs.
     */
    public static function getRecentLogs(int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs by event type.
     */
    public static function getLogsByEventType(string $eventType, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::eventType($eventType)
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs for a specific model.
     */
    public static function getModelLogs(Model $model, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::forModel(get_class($model), $model->getKey())
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search audit logs.
     */
    public static function searchLogs(array $criteria): \Illuminate\Database\Eloquent\Builder
    {
        $query = AuditLog::query();

        if (isset($criteria['event_type'])) {
            $query->where('event_type', $criteria['event_type']);
        }

        if (isset($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }

        if (isset($criteria['ip_address'])) {
            $query->where('ip_address', $criteria['ip_address']);
        }

        if (isset($criteria['start_date'])) {
            $query->where('performed_at', '>=', $criteria['start_date']);
        }

        if (isset($criteria['end_date'])) {
            $query->where('performed_at', '<=', $criteria['end_date']);
        }

        if (isset($criteria['auditable_type'])) {
            $query->where('auditable_type', $criteria['auditable_type']);
        }

        if (isset($criteria['description'])) {
            $query->where('description', 'like', '%'.$criteria['description'].'%');
        }

        return $query->orderBy('performed_at', 'desc');
    }

    /**
     * Get audit statistics.
     */
    public static function getStats(int $days = 30): array
    {
        $auditService = app(AuditService::class);

        return $auditService->getAuditStats($days);
    }

    /**
     * Clean up old audit logs.
     */
    public static function cleanup(int $daysToKeep = 90): int
    {
        $auditService = app(AuditService::class);

        return $auditService->cleanupOldLogs($daysToKeep);
    }

    /**
     * Check if current user can view audit logs.
     */
    public static function canViewAuditLogs(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Check if user has permission to view audit logs
        return $user->can('view_audit_logs') || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Check if current user can manage audit logs.
     */
    public static function canManageAuditLogs(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Check if user has permission to manage audit logs
        return $user->can('manage_audit_logs') || $user->hasRole(['admin', 'super_admin']);
    }
}
