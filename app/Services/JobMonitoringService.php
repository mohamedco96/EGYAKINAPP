<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobMonitoringService
{
    /**
     * Get job statistics for monitoring
     */
    public function getJobStatistics(): array
    {
        $stats = [
            'failed_jobs' => $this->getFailedJobsCount(),
            'pending_jobs' => $this->getPendingJobsCount(),
            'recent_failures' => $this->getRecentFailures(),
            'job_failure_rate' => $this->getJobFailureRate(),
            'critical_failures' => $this->getCriticalFailures(),
            'last_check' => now()->toISOString(),
        ];

        // Cache the stats for 5 minutes
        Cache::put('job_monitoring_stats', $stats, 300);

        return $stats;
    }

    /**
     * Get count of failed jobs
     */
    public function getFailedJobsCount(): int
    {
        try {
            return DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            Log::warning('Failed to get failed jobs count', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Get count of pending jobs
     */
    public function getPendingJobsCount(): int
    {
        try {
            return DB::table('jobs')->count();
        } catch (\Exception $e) {
            Log::warning('Failed to get pending jobs count', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Get recent job failures (last 24 hours)
     */
    public function getRecentFailures(): array
    {
        try {
            $failures = DB::table('failed_jobs')
                ->where('failed_at', '>=', Carbon::now()->subDay())
                ->orderBy('failed_at', 'desc')
                ->limit(10)
                ->get(['id', 'queue', 'payload', 'exception', 'failed_at'])
                ->map(function ($failure) {
                    $payload = json_decode($failure->payload, true);

                    return [
                        'id' => $failure->id,
                        'queue' => $failure->queue,
                        'job_class' => $payload['displayName'] ?? 'Unknown',
                        'exception' => substr($failure->exception, 0, 200).'...',
                        'failed_at' => $failure->failed_at,
                    ];
                })
                ->toArray();

            return $failures;
        } catch (\Exception $e) {
            Log::warning('Failed to get recent failures', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Calculate job failure rate (last 24 hours)
     */
    public function getJobFailureRate(): float
    {
        try {
            $totalJobs = $this->getTotalJobsProcessed();
            $failedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', Carbon::now()->subDay())
                ->count();

            if ($totalJobs === 0) {
                return 0.0;
            }

            return round(($failedJobs / $totalJobs) * 100, 2);
        } catch (\Exception $e) {
            Log::warning('Failed to calculate job failure rate', ['error' => $e->getMessage()]);

            return 0.0;
        }
    }

    /**
     * Get critical job failures (email, notifications, etc.)
     */
    public function getCriticalFailures(): array
    {
        try {
            $criticalJobClasses = [
                'App\\Mail\\',
                'App\\Notifications\\',
                'App\\Console\\Commands\\SendDailyReport',
                'App\\Console\\Commands\\SendWeeklySummary',
                'App\\Console\\Commands\\SendReminderEmails',
            ];

            $failures = DB::table('failed_jobs')
                ->where('failed_at', '>=', Carbon::now()->subHour())
                ->get(['id', 'queue', 'payload', 'exception', 'failed_at'])
                ->filter(function ($failure) use ($criticalJobClasses) {
                    $payload = json_decode($failure->payload, true);
                    $jobClass = $payload['displayName'] ?? '';

                    foreach ($criticalJobClasses as $criticalClass) {
                        if (str_contains($jobClass, $criticalClass)) {
                            return true;
                        }
                    }

                    return false;
                })
                ->map(function ($failure) {
                    $payload = json_decode($failure->payload, true);

                    return [
                        'id' => $failure->id,
                        'job_class' => $payload['displayName'] ?? 'Unknown',
                        'exception' => substr($failure->exception, 0, 300).'...',
                        'failed_at' => $failure->failed_at,
                    ];
                })
                ->toArray();

            return $failures;
        } catch (\Exception $e) {
            Log::warning('Failed to get critical failures', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Log job monitoring alert
     */
    public function logAlert(string $type, string $message, array $context = []): void
    {
        Log::channel('job_monitoring')->warning("Job Monitoring Alert: {$type}", [
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check for job monitoring alerts
     */
    public function checkForAlerts(): array
    {
        $alerts = [];
        $stats = $this->getJobStatistics();

        // Alert if too many failed jobs
        if ($stats['failed_jobs'] > 10) {
            $alerts[] = [
                'type' => 'high_failure_count',
                'message' => "High number of failed jobs: {$stats['failed_jobs']}",
                'severity' => 'warning',
            ];
        }

        // Alert if high failure rate
        if ($stats['job_failure_rate'] > 20) {
            $alerts[] = [
                'type' => 'high_failure_rate',
                'message' => "High job failure rate: {$stats['job_failure_rate']}%",
                'severity' => 'warning',
            ];
        }

        // Alert if critical failures
        if (! empty($stats['critical_failures'])) {
            $alerts[] = [
                'type' => 'critical_failures',
                'message' => 'Critical job failures detected: '.count($stats['critical_failures']).' failures',
                'severity' => 'critical',
            ];
        }

        // Alert if too many pending jobs (queue backup)
        if ($stats['pending_jobs'] > 100) {
            $alerts[] = [
                'type' => 'queue_backup',
                'message' => "Queue backup detected: {$stats['pending_jobs']} pending jobs",
                'severity' => 'warning',
            ];
        }

        return $alerts;
    }

    /**
     * Get total jobs processed (estimate based on logs)
     */
    private function getTotalJobsProcessed(): int
    {
        // This is an estimate - in a real implementation you might want to track this more precisely
        $failedJobs = DB::table('failed_jobs')
            ->where('failed_at', '>=', Carbon::now()->subDay())
            ->count();

        // Assume 95% success rate as baseline
        return max($failedJobs * 20, 1);
    }

    /**
     * Clean up old monitoring data
     */
    public function cleanup(): void
    {
        try {
            // Clean up failed jobs older than 7 days
            DB::table('failed_jobs')
                ->where('failed_at', '<', Carbon::now()->subWeek())
                ->delete();

            Log::info('Job monitoring cleanup completed');
        } catch (\Exception $e) {
            Log::error('Job monitoring cleanup failed', ['error' => $e->getMessage()]);
        }
    }
}
