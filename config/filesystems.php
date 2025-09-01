<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Cleanup Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the file cleanup system
    |
    */

    'cleanup' => [

        /*
        |--------------------------------------------------------------------------
        | Enable/Disable File Cleanup
        |--------------------------------------------------------------------------
        |
        | This option allows you to enable or disable the automatic file cleanup
        | system. When disabled, orphaned files will not be automatically removed.
        |
        */

        'enabled' => env('FILE_CLEANUP_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Automatic Cleanup on Model Deletion
        |--------------------------------------------------------------------------
        |
        | When enabled, files will be automatically deleted when their associated
        | database records are removed.
        |
        */

        'auto_cleanup_on_delete' => env('FILE_AUTO_CLEANUP_ON_DELETE', true),

        /*
        |--------------------------------------------------------------------------
        | Scheduled Cleanup Configuration
        |--------------------------------------------------------------------------
        |
        | Configuration for the scheduled cleanup job that runs periodically
        | to remove orphaned files.
        |
        */

        'schedule' => [
            'enabled' => env('SCHEDULED_CLEANUP_ENABLED', true),
            'frequency' => env('SCHEDULED_CLEANUP_FREQUENCY', 'daily'), // daily, weekly, monthly
            'time' => env('SCHEDULED_CLEANUP_TIME', '02:00'), // Time to run (24-hour format)
            'disk' => env('SCHEDULED_CLEANUP_DISK', 'public'),
            'batch_size' => env('SCHEDULED_CLEANUP_BATCH_SIZE', 100),
        ],

        /*
        |--------------------------------------------------------------------------
        | File Retention Period
        |--------------------------------------------------------------------------
        |
        | Files older than this period (in days) will be considered for cleanup
        | even if they appear to be orphaned. This adds an extra safety layer.
        |
        */

        'retention_days' => env('FILE_RETENTION_DAYS', 7),

        /*
        |--------------------------------------------------------------------------
        | File Patterns to Monitor
        |--------------------------------------------------------------------------
        |
        | Define which file patterns should be monitored for cleanup.
        | Each pattern specifies the model, column, and data type.
        |
        */

        'patterns' => [
            'media_images' => [
                'model' => \App\Models\FeedPost::class,
                'column' => 'media_path',
                'type' => 'json_array',
                'enabled' => true,
            ],
            'profile_images' => [
                'model' => \App\Models\User::class,
                'column' => 'image',
                'type' => 'string',
                'enabled' => true,
            ],
            // Add more patterns as needed
        ],

        /*
        |--------------------------------------------------------------------------
        | Exclude Patterns
        |--------------------------------------------------------------------------
        |
        | Files matching these patterns will never be deleted during cleanup.
        | Use glob patterns (*, ?, [abc], etc.)
        |
        */

        'exclude_patterns' => [
            'default_profile.png',
            'placeholder.jpg',
            'logo.*',
            '.gitkeep',
            '.DS_Store',
            'thumbs.db',
            'Thumbs.db',
        ],

        /*
        |--------------------------------------------------------------------------
        | Logging Configuration
        |--------------------------------------------------------------------------
        |
        | Configuration for cleanup operation logging
        |
        */

        'logging' => [
            'enabled' => env('FILE_CLEANUP_LOGGING_ENABLED', true),
            'channel' => env('FILE_CLEANUP_LOG_CHANNEL', 'file_cleanup'),
            'level' => env('FILE_CLEANUP_LOG_LEVEL', 'info'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Safety Settings
        |--------------------------------------------------------------------------
        |
        | Additional safety measures to prevent accidental file deletion
        |
        */

        'safety' => [
            'max_files_per_run' => env('CLEANUP_MAX_FILES_PER_RUN', 1000),
            'require_confirmation' => env('CLEANUP_REQUIRE_CONFIRMATION', true),
            'backup_before_delete' => env('CLEANUP_BACKUP_BEFORE_DELETE', false),
            'backup_disk' => env('CLEANUP_BACKUP_DISK', 'local'),
        ],
    ],

];
