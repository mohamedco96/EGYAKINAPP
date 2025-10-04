# Audit System Documentation

## Overview

The audit system provides comprehensive tracking of all activities within the EGYAKIN application. It automatically logs user actions, model changes, API requests, authentication events, and more to ensure complete accountability and security monitoring.

## Features

- **Automatic Model Auditing**: Track create, update, delete operations on all models
- **HTTP Request Logging**: Log API calls and web requests with performance metrics
- **Authentication Tracking**: Monitor login, logout, failed attempts, and security events
- **Filament Admin Interface**: View and manage audit logs through the admin panel
- **Configurable Filtering**: Skip sensitive data and unnecessary logs
- **Performance Optimized**: Asynchronous processing with queue support
- **Data Retention**: Automatic cleanup of old logs
- **Security Features**: Sensitive data filtering and access controls

## Installation & Setup

### 1. Run Migration

```bash
php artisan migrate
```

This will create the `audit_logs` table with all necessary fields.

### 2. Configure Queue (Recommended)

Add an audit queue to your `config/queue.php`:

```php
'connections' => [
    // ... other connections
    'audit' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'audit',
        'retry_after' => 90,
    ],
],
```

### 3. Environment Variables

Add these to your `.env` file:

```env
# Enable/disable audit system
AUDIT_ENABLED=true

# Queue for audit processing
AUDIT_QUEUE=audit

# Days to keep audit logs
AUDIT_RETENTION_DAYS=90

# Enable HTTP request auditing
AUDIT_HTTP_ENABLED=true

# Enable authentication event auditing
AUDIT_AUTH_ENABLED=true

# Process audit logs asynchronously
AUDIT_ASYNC=true

# Enable audit notifications
AUDIT_NOTIFICATIONS_ENABLED=false
```

## Usage

### Automatic Auditing

The system automatically audits:

- **Model Changes**: All create, update, delete operations on registered models
- **Authentication Events**: Login, logout, failed attempts, password resets
- **API Requests**: All API calls with request/response data
- **Security Events**: Permission changes, role assignments, suspicious activities

### Manual Auditing

Use the `AuditHelper` class for custom audit events:

```php
use App\Helpers\AuditHelper;

// Log a custom event
AuditHelper::log('custom_event', 'User performed custom action', [
    'additional_data' => 'value'
]);

// Log authentication events
AuditHelper::logLogin($user);
AuditHelper::logLogout($user);
AuditHelper::logFailedLogin('user@example.com');

// Log file operations
AuditHelper::logFileUpload('document.pdf');
AuditHelper::logFileDownload('report.xlsx');

// Log security events
AuditHelper::logSecurityEvent('suspicious_activity', 'Multiple failed login attempts');
AuditHelper::logPermissionChange($user, 'manage_users', 'granted');

// Log data operations
AuditHelper::logDataExport('user_data');
AuditHelper::logDataImport('patient_records', 150);
```

### Using the Auditable Trait

Add the `Auditable` trait to models for enhanced auditing:

```php
use App\Traits\Auditable;

class MyModel extends Model
{
    use Auditable;
    
    // Customize audited attributes
    public function getAuditableAttributes(): array
    {
        return ['name', 'email', 'status'];
    }
    
    // Exclude sensitive attributes
    public function getAuditExcludedAttributes(): array
    {
        return ['password', 'api_token'];
    }
}
```

### Querying Audit Logs

```php
use App\Models\AuditLog;

// Get recent logs
$recentLogs = AuditLog::orderBy('performed_at', 'desc')->limit(100)->get();

// Filter by event type
$loginLogs = AuditLog::eventType('login')->get();

// Filter by user
$userLogs = AuditLog::byUser($userId)->get();

// Filter by model
$modelLogs = AuditLog::forModel(User::class, $userId)->get();

// Date range filtering
$logs = AuditLog::dateRange($startDate, $endDate)->get();

// Search with multiple criteria
$logs = AuditHelper::searchLogs([
    'event_type' => 'login',
    'start_date' => now()->subDays(7),
    'ip_address' => '192.168.1.1'
])->get();
```

## Admin Interface

Access audit logs through the Filament admin panel:

1. Navigate to **Security > Audit Logs**
2. Use filters to find specific events:
   - Event Type (login, created, updated, etc.)
   - Date Range
   - User
   - Device Type
3. View detailed information by clicking on any log entry
4. Export logs for external analysis

### Permissions

Control access to audit logs using roles and permissions:

```php
// Check if user can view audit logs
if (AuditHelper::canViewAuditLogs()) {
    // Show audit interface
}

// Check if user can manage audit logs
if (AuditHelper::canManageAuditLogs()) {
    // Allow deletion/cleanup
}
```

## Console Commands

### Cleanup Old Logs

```bash
# Clean up logs older than 90 days (default)
php artisan audit:cleanup

# Clean up logs older than 30 days
php artisan audit:cleanup --days=30
```

### View Statistics

```bash
# Show audit statistics for last 30 days (default)
php artisan audit:stats

# Show statistics for last 7 days
php artisan audit:stats --days=7
```

## Configuration

The audit system is highly configurable through `config/audit.php`:

### Key Settings

- **`enabled`**: Enable/disable the entire audit system
- **`queue`**: Queue name for async processing
- **`retention_days`**: Days to keep audit logs
- **`auditable_models`**: Models to automatically audit
- **`sensitive_fields`**: Fields to filter from logs
- **`http.enabled`**: Enable HTTP request auditing
- **`auth.enabled`**: Enable authentication event auditing

### Performance Tuning

- **`performance.async_processing`**: Process logs asynchronously
- **`performance.batch_size`**: Batch size for cleanup operations
- **`performance.max_request_data_size`**: Limit request data size

### Security Options

- **`security.hash_sensitive_data`**: Hash instead of filter sensitive data
- **`security.encrypt_logs`**: Encrypt audit logs at rest
- **`security.anonymize_ip`**: Anonymize IP addresses

## Security Considerations

### Data Privacy

- Sensitive fields are automatically filtered from audit logs
- Configure `sensitive_fields` in the config file to add custom fields
- Consider enabling IP anonymization for GDPR compliance

### Access Control

- Only users with appropriate roles can view audit logs
- Audit logs themselves are not audited to prevent infinite loops
- Consider encrypting audit logs for sensitive environments

### Performance Impact

- Use async processing in production to minimize performance impact
- Configure appropriate queue workers for the audit queue
- Set reasonable retention periods to manage database size

## Monitoring & Alerts

### Suspicious Activity Detection

The system can detect and alert on suspicious activities:

- Multiple failed login attempts
- Unusual access patterns
- Bulk data operations
- Permission escalations

### Notifications

Configure notifications in `config/audit.php`:

```php
'notifications' => [
    'enabled' => true,
    'suspicious_activity_threshold' => 5,
    'time_window' => 15, // minutes
    'channels' => ['mail', 'database'],
    'recipients' => ['admin@example.com'],
],
```

## Troubleshooting

### Common Issues

1. **High Database Growth**
   - Reduce retention period
   - Exclude noisy models/events
   - Enable async processing

2. **Performance Issues**
   - Ensure queue workers are running
   - Increase batch sizes
   - Skip read-only operations

3. **Missing Logs**
   - Check if audit system is enabled
   - Verify model observers are registered
   - Check queue processing

### Debug Mode

Enable debug logging by setting `LOG_LEVEL=debug` in your `.env` file.

## Best Practices

1. **Queue Configuration**: Always use queues in production
2. **Regular Cleanup**: Schedule the cleanup command to run daily
3. **Monitor Storage**: Keep an eye on database size growth
4. **Access Control**: Restrict audit log access to authorized personnel
5. **Backup Strategy**: Include audit logs in your backup strategy
6. **Compliance**: Configure retention periods according to your compliance requirements

## API Reference

### AuditService Methods

- `logModelEvent(string $eventType, Model $model, array $oldValues = [], array $newValues = [])`
- `logAuthEvent(string $eventType, ?User $user = null, array $metadata = [])`
- `logCustomEvent(string $eventType, ?string $description = null, array $metadata = [])`
- `logApiRequest(Request $request, $response = null, ?User $user = null)`
- `getModelAuditLogs(Model $model, int $limit = 50)`
- `getUserAuditLogs(User $user, int $limit = 50)`
- `cleanupOldLogs(int $daysToKeep = 90)`
- `getAuditStats(int $days = 30)`

### AuditHelper Methods

- `log(string $eventType, ?string $description = null, array $metadata = [])`
- `logAuth(string $eventType, ?User $user = null, array $metadata = [])`
- `logLogin(?User $user = null, array $metadata = [])`
- `logLogout(?User $user = null, array $metadata = [])`
- `logFailedLogin(string $email, array $metadata = [])`
- `logFileOperation(string $operation, string $filename, array $metadata = [])`
- `logSecurityEvent(string $eventType, string $description, array $metadata = [])`
- `searchLogs(array $criteria)`
- `getStats(int $days = 30)`
- `cleanup(int $daysToKeep = 90)`

## Support

For issues or questions about the audit system:

1. Check the logs in `storage/logs/laravel.log`
2. Review the configuration in `config/audit.php`
3. Ensure all migrations have been run
4. Verify queue workers are processing the audit queue
