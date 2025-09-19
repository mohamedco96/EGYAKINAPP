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
        // Run the command every minute
        $schedule->command('reminder:send')->daily();

        // === EMAIL REPORTING SYSTEM ===

        // Daily Report - Send every day at 08:00 AM
        $schedule->command('reports:send-daily')
            ->dailyAt('08:00')
            ->withoutOverlapping(30) // Prevent overlapping runs, timeout after 30 minutes
            ->runInBackground()
            ->emailOutputOnFailure(config('mail.admin_email'))
            ->appendOutputTo(storage_path('logs/daily_reports.log'))
            ->onFailure(function () {
                Log::error('Daily report scheduled job failed', [
                    'timestamp' => now()->toISOString(),
                    'scheduled_time' => '08:00',
                ]);
            })
            ->onSuccess(function () {
                Log::info('Daily report scheduled job completed successfully', [
                    'timestamp' => now()->toISOString(),
                    'scheduled_time' => '08:00',
                ]);
            });

        // Test Daily Report - Send every minute for immediate testing
        $schedule->command('reports:send-daily --email=mohamedco215@gmail.com')
            ->everyMinute()
            ->withoutOverlapping(2) // Prevent overlapping runs, timeout after 2 minutes
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cron.log'))
            ->onFailure(function () {
                Log::error('Test daily report scheduled job failed', [
                    'timestamp' => now()->toISOString(),
                    'scheduled_time' => 'every minute',
                ]);
            })
            ->onSuccess(function () {
                Log::info('Test daily report scheduled job completed successfully', [
                    'timestamp' => now()->toISOString(),
                    'scheduled_time' => 'every minute',
                ]);
            });

        // Weekly Summary - Send every Monday at 09:00 AM
        $schedule->command('reports:send-weekly')
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
