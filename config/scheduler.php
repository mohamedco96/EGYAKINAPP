<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scheduler Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the Laravel scheduler.
    | You can enable/disable scheduled tasks based on environment.
    |
    */

    'enabled' => env('SCHEDULER_ENABLED', app()->environment('production')),

    'tasks' => [
        'reminder_send' => env('SCHEDULER_REMINDER_SEND', true),
        'daily_reports' => env('SCHEDULER_DAILY_REPORTS', true),
        'weekly_reports' => env('SCHEDULER_WEEKLY_REPORTS', true),
        'file_cleanup' => env('SCHEDULER_FILE_CLEANUP', true),
        'job_monitoring' => env('SCHEDULER_JOB_MONITORING', true),
        'filament_excel_prune' => env('SCHEDULER_FILAMENT_EXCEL_PRUNE', true),
    ],

    'environments' => [
        'production' => [
            'all_tasks' => true,
        ],
        'staging' => [
            'all_tasks' => false,
            'allowed_tasks' => ['job_monitoring'], // Only monitoring in staging
        ],
        'testing' => [
            'all_tasks' => false,
        ],
        'local' => [
            'all_tasks' => false,
        ],
    ],
];
