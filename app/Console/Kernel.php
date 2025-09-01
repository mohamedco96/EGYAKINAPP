<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

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
