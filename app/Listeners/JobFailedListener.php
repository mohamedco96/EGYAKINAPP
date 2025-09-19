<?php

namespace App\Listeners;

use App\Services\JobMonitoringService;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

class JobFailedListener
{
    /**
     * Handle the event.
     */
    public function handle(JobFailed $event): void
    {
        $jobClass = $event->job->resolveName();
        $exception = $event->exception;
        $connectionName = $event->connectionName;
        $queue = $event->job->getQueue();

        // Enhanced logging for failed jobs
        Log::channel('job_monitoring')->error('Job failed', [
            'job_class' => $jobClass,
            'connection' => $connectionName,
            'queue' => $queue,
            'attempts' => $event->job->attempts(),
            'exception' => $exception->getMessage(),
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'payload' => $event->job->payload(),
            'failed_at' => now()->toISOString(),
        ]);

        // Check if this is a critical job failure
        $criticalJobClasses = [
            'App\\Mail\\',
            'App\\Notifications\\',
            'App\\Console\\Commands\\SendDailyReport',
            'App\\Console\\Commands\\SendWeeklySummary',
            'App\\Console\\Commands\\SendReminderEmails',
        ];

        $isCritical = false;
        foreach ($criticalJobClasses as $criticalClass) {
            if (str_contains($jobClass, $criticalClass)) {
                $isCritical = true;
                break;
            }
        }

        if ($isCritical) {
            Log::channel('job_monitoring')->critical('Critical job failed', [
                'job_class' => $jobClass,
                'exception' => $exception->getMessage(),
                'failed_at' => now()->toISOString(),
            ]);

            // Log alert for immediate attention
            $monitoringService = new JobMonitoringService();
            $monitoringService->logAlert(
                'critical_job_failure',
                "Critical job failed: {$jobClass} - {$exception->getMessage()}",
                [
                    'job_class' => $jobClass,
                    'connection' => $connectionName,
                    'queue' => $queue,
                    'exception' => $exception->getMessage(),
                ]
            );
        }
    }
}
