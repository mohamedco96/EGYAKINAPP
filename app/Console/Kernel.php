<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        // Patient Outcome Reminder System - Check every 6 hours for patients needing outcome reminders
        $schedule->command('reminder:send')
            ->everySixHours()
            ->withoutOverlapping(60) // Prevent overlapping runs, timeout after 1 hour
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/reminder_emails.log'))
            ->onFailure(function () {
                Log::error('Reminder email scheduled job failed', [
                    'timestamp' => now()->toISOString(),
                    'command' => 'reminder:send',
                ]);
            })
            ->onSuccess(function () {
                Log::info('Reminder email scheduled job completed successfully', [
                    'timestamp' => now()->toISOString(),
                    'command' => 'reminder:send',
                ]);
            });

        // === EMAIL REPORTING SYSTEM ===

        // Daily Report - Send every day at 09:00 AM to mail list
        $schedule->command('reports:send-daily --mail-list')
            ->dailyAt('09:00')
            ->withoutOverlapping(30) // Prevent overlapping runs, timeout after 30 minutes
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cron.log'))
            ->onFailure(function () {
                Log::error('Daily report scheduled job failed', [
                    'timestamp' => now()->toISOString(),
                    'scheduled_time' => '09:00',
                ]);
            })
            ->onSuccess(function () {
                Log::info('Daily report scheduled job completed successfully', [
                    'timestamp' => now()->toISOString(),
                    'scheduled_time' => '09:00',
                ]);
            });

        // Weekly Summary - Send every Monday at 09:00 AM to mail list
        $schedule->command('reports:send-weekly --mail-list')
            ->weeklyOn(1, '09:00') // Monday at 09:00 AM
            ->withoutOverlapping(60) // Prevent overlapping runs, timeout after 1 hour
            ->runInBackground()
            ->emailOutputOnFailure(config('mail.admin_email'))
            ->appendOutputTo(storage_path('logs/weekly_summaries.log'))
            ->onFailure(function () {
                Log::error('Weekly summary scheduled job failed', [
                    'timestamp' => now()->toISOString(),
                    'scheduled_time' => 'Monday 09:00',
                ]);
            })
            ->onSuccess(function () {
                Log::info('Weekly summary scheduled job completed successfully', [
                    'timestamp' => now()->toISOString(),
                    'scheduled_time' => 'Monday 09:00',
                ]);
            });

        // File cleanup scheduled job
        if (config('filesystems.cleanup.schedule.enabled', true)) {
            $frequency = config('filesystems.cleanup.schedule.frequency', 'daily');
            $time = config('filesystems.cleanup.schedule.time', '02:00');
            $disk = config('filesystems.cleanup.schedule.disk', 'public');
            $batchSize = config('filesystems.cleanup.schedule.batch_size', 100);

            $command = $schedule->command("files:cleanup --disk={$disk} --batch-size={$batchSize} --force");

            switch ($frequency) {
                case 'weekly':
                    $command->weekly()->at($time);
                    break;
                case 'monthly':
                    $command->monthly()->at($time);
                    break;
                case 'daily':
                default:
                    $command->daily()->at($time);
                    break;
            }

            // Add additional scheduling options
            $command->withoutOverlapping(120) // Prevent overlapping runs, timeout after 2 hours
                ->runInBackground()
                ->emailOutputOnFailure(config('mail.admin_email'))
                ->appendOutputTo(storage_path('logs/scheduled_cleanup.log'));
        }

        // Job Monitoring - Check every 15 minutes
        $schedule->command('jobs:monitor')
            ->everyFifteenMinutes()
            ->withoutOverlapping(10) // Prevent overlapping runs, timeout after 10 minutes
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/job_monitoring.log'))
            ->onFailure(function () {
                Log::error('Job monitoring scheduled task failed', [
                    'timestamp' => now()->toISOString(),
                    'command' => 'jobs:monitor',
                ]);
            });

        // Job Monitoring with Alerts - Check every hour for critical issues
        $schedule->command('jobs:monitor --alert')
            ->hourly()
            ->withoutOverlapping(15) // Prevent overlapping runs, timeout after 15 minutes
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/job_monitoring.log'))
            ->onFailure(function () {
                Log::error('Job monitoring alert scheduled task failed', [
                    'timestamp' => now()->toISOString(),
                    'command' => 'jobs:monitor --alert',
                ]);
            });

        // Job Monitoring Cleanup - Run daily at 3 AM
        $schedule->command('jobs:monitor --cleanup')
            ->dailyAt('03:00')
            ->withoutOverlapping(30) // Prevent overlapping runs, timeout after 30 minutes
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/job_monitoring.log'))
            ->onFailure(function () {
                Log::error('Job monitoring cleanup scheduled task failed', [
                    'timestamp' => now()->toISOString(),
                    'command' => 'jobs:monitor --cleanup',
                ]);
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
