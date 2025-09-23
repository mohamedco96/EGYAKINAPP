<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the audit system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Auditing
    |--------------------------------------------------------------------------
    |
    | This option allows you to enable or disable the entire audit system.
    | When disabled, no audit logs will be created.
    |
    */

    'enabled' => env('AUDIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Audit Queue
    |--------------------------------------------------------------------------
    |
    | The queue that audit jobs should be dispatched to. Set to null to
    | process audit logs synchronously (not recommended for production).
    |
    */

    'queue' => env('AUDIT_QUEUE', 'audit'),

    /*
    |--------------------------------------------------------------------------
    | Audit Log Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to keep audit logs. Older logs will be automatically
    | deleted when running the audit:cleanup command.
    |
    */

    'retention_days' => env('AUDIT_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Auditable Models
    |--------------------------------------------------------------------------
    |
    | List of models that should be automatically audited. You can also
    | use the Auditable trait on individual models for more control.
    |
    */

    'auditable_models' => [
        \App\Models\User::class,
        \App\Models\Score::class,
        \App\Models\FeedPost::class,
        \App\Models\Group::class,
        \App\Models\Questions::class,
        \App\Models\Answers::class,
        \App\Models\SectionsInfo::class,

        // Module models (will be checked for existence)
        \App\Modules\Patients\Models\Patients::class,
        \App\Modules\Posts\Models\Posts::class,
        \App\Modules\Posts\Models\PostComments::class,
        \App\Modules\Achievements\Models\Achievement::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Models
    |--------------------------------------------------------------------------
    |
    | Models that should never be audited, even if they use the Auditable trait.
    |
    */

    'excluded_models' => [
        \App\Models\AuditLog::class,
        \Laravel\Sanctum\PersonalAccessToken::class,
        \Illuminate\Notifications\DatabaseNotification::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Fields
    |--------------------------------------------------------------------------
    |
    | Fields that contain sensitive data and should be filtered out from
    | audit logs or marked as [FILTERED].
    |
    */

    'sensitive_fields' => [
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
        'remember_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Request Auditing
    |--------------------------------------------------------------------------
    |
    | Configuration for HTTP request auditing middleware.
    |
    */

    'http' => [
        /*
        |--------------------------------------------------------------------------
        | Enable HTTP Request Auditing
        |--------------------------------------------------------------------------
        */
        'enabled' => env('AUDIT_HTTP_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Skip Routes
        |--------------------------------------------------------------------------
        |
        | Route patterns that should be skipped from HTTP auditing.
        |
        */
        'skip_routes' => [
            'telescope*',
            'horizon*',
            '_debugbar*',
            'livewire*',
            'health*',
            'ping*',
            'status*',
            // 'api/*/upload*', // Uncomment to skip file upload endpoints
        ],

        /*
        |--------------------------------------------------------------------------
        | Skip Static Assets
        |--------------------------------------------------------------------------
        |
        | Whether to skip static assets (CSS, JS, images, etc.) from auditing.
        |
        */
        'skip_static_assets' => true,

        /*
        |--------------------------------------------------------------------------
        | Skip Read-Only Routes
        |--------------------------------------------------------------------------
        |
        | Whether to skip GET requests to read-only routes to reduce noise.
        |
        */
        'skip_read_only' => true,

        /*
        |--------------------------------------------------------------------------
        | Read-Only Route Patterns
        |--------------------------------------------------------------------------
        |
        | Patterns for routes that are considered read-only.
        |
        */
        'read_only_patterns' => [
            'api/*/index',
            'api/*/show',
            'filament/assets/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Events
    |--------------------------------------------------------------------------
    |
    | Configuration for auditing authentication events.
    |
    */

    'auth' => [
        /*
        |--------------------------------------------------------------------------
        | Enable Authentication Auditing
        |--------------------------------------------------------------------------
        */
        'enabled' => env('AUDIT_AUTH_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Track Failed Login Attempts
        |--------------------------------------------------------------------------
        */
        'track_failed_logins' => true,

        /*
        |--------------------------------------------------------------------------
        | Track Password Resets
        |--------------------------------------------------------------------------
        */
        'track_password_resets' => true,

        /*
        |--------------------------------------------------------------------------
        | Track Email Verifications
        |--------------------------------------------------------------------------
        */
        'track_email_verifications' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings to optimize audit system performance.
    |
    */

    'performance' => [
        /*
        |--------------------------------------------------------------------------
        | Batch Size
        |--------------------------------------------------------------------------
        |
        | Number of audit logs to process in a single batch when cleaning up.
        |
        */
        'batch_size' => 1000,

        /*
        |--------------------------------------------------------------------------
        | Max Request Data Size
        |--------------------------------------------------------------------------
        |
        | Maximum size (in bytes) of request data to store in audit logs.
        | Larger requests will be truncated.
        |
        */
        'max_request_data_size' => 10240, // 10KB

        /*
        |--------------------------------------------------------------------------
        | Async Processing
        |--------------------------------------------------------------------------
        |
        | Whether to process audit logs asynchronously using queues.
        |
        */
        'async_processing' => env('AUDIT_ASYNC', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration for the audit system.
    |
    */

    'security' => [
        /*
        |--------------------------------------------------------------------------
        | Hash Sensitive Data
        |--------------------------------------------------------------------------
        |
        | Whether to hash sensitive data instead of filtering it out completely.
        |
        */
        'hash_sensitive_data' => false,

        /*
        |--------------------------------------------------------------------------
        | Encrypt Audit Logs
        |--------------------------------------------------------------------------
        |
        | Whether to encrypt audit log data at rest.
        |
        */
        'encrypt_logs' => false,

        /*
        |--------------------------------------------------------------------------
        | IP Address Anonymization
        |--------------------------------------------------------------------------
        |
        | Whether to anonymize IP addresses in audit logs for privacy.
        |
        */
        'anonymize_ip' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for audit-related notifications.
    |
    */

    'notifications' => [
        /*
        |--------------------------------------------------------------------------
        | Enable Notifications
        |--------------------------------------------------------------------------
        */
        'enabled' => env('AUDIT_NOTIFICATIONS_ENABLED', false),

        /*
        |--------------------------------------------------------------------------
        | Suspicious Activity Threshold
        |--------------------------------------------------------------------------
        |
        | Number of failed login attempts within the time window that triggers
        | a suspicious activity notification.
        |
        */
        'suspicious_activity_threshold' => 5,

        /*
        |--------------------------------------------------------------------------
        | Time Window (minutes)
        |--------------------------------------------------------------------------
        |
        | Time window for counting suspicious activities.
        |
        */
        'time_window' => 15,

        /*
        |--------------------------------------------------------------------------
        | Notification Channels
        |--------------------------------------------------------------------------
        |
        | Channels to send audit notifications to.
        |
        */
        'channels' => ['mail', 'database'],

        /*
        |--------------------------------------------------------------------------
        | Notification Recipients
        |--------------------------------------------------------------------------
        |
        | Email addresses to send audit notifications to.
        |
        */
        'recipients' => [
            // 'admin@example.com',
        ],
    ],

];
