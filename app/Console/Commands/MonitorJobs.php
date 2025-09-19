<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\JobMonitoringAlert;
use App\Services\JobMonitoringService;
use App\Services\MailListService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorJobs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jobs:monitor 
                            {--alert : Send alerts for critical issues}
                            {--cleanup : Clean up old monitoring data}
                            {--stats : Show detailed statistics}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor job queue status and send alerts for failures';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Starting Job Queue Monitoring...');

        $monitoringService = new JobMonitoringService();

        try {
            // Get job statistics
            $stats = $monitoringService->getJobStatistics();

            // Display basic stats
            $this->displayBasicStats($stats);

            // Show detailed statistics if requested
            if ($this->option('stats')) {
                $this->displayDetailedStats($stats);
            }

            // Check for alerts
            $alerts = $monitoringService->checkForAlerts();

            if (! empty($alerts)) {
                $this->displayAlerts($alerts);

                // Send alerts if requested
                if ($this->option('alert')) {
                    $this->sendAlerts($alerts);
                }
            } else {
                $this->info('✅ No alerts detected - all systems normal');
            }

            // Cleanup if requested
            if ($this->option('cleanup')) {
                $this->info('🧹 Cleaning up old monitoring data...');
                $monitoringService->cleanup();
                $this->info('✅ Cleanup completed');
            }

            Log::info('Job monitoring check completed', [
                'stats' => $stats,
                'alerts_count' => count($alerts),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Job monitoring failed: '.$e->getMessage());

            Log::error('Job monitoring command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Display basic job statistics
     */
    private function displayBasicStats(array $stats): void
    {
        $this->info('📊 Job Queue Statistics');
        $this->info('═══════════════════════════════════════');

        $this->line("📋 Pending Jobs: {$stats['pending_jobs']}");
        $this->line("❌ Failed Jobs: {$stats['failed_jobs']}");
        $this->line("📈 Failure Rate (24h): {$stats['job_failure_rate']}%");
        $this->line('🚨 Critical Failures (1h): '.count($stats['critical_failures']));
        $this->line("🕒 Last Check: {$stats['last_check']}");
        $this->line('');
    }

    /**
     * Display detailed statistics
     */
    private function displayDetailedStats(array $stats): void
    {
        $this->info('📋 Recent Failures (Last 24 Hours)');
        $this->info('═══════════════════════════════════════');

        if (empty($stats['recent_failures'])) {
            $this->line('✅ No recent failures');
        } else {
            foreach ($stats['recent_failures'] as $failure) {
                $this->line("🔸 ID: {$failure['id']} | Queue: {$failure['queue']}");
                $this->line("  Job: {$failure['job_class']}");
                $this->line("  Failed: {$failure['failed_at']}");
                $this->line("  Error: {$failure['exception']}");
                $this->line('');
            }
        }

        if (! empty($stats['critical_failures'])) {
            $this->info('🚨 Critical Failures (Last Hour)');
            $this->info('═══════════════════════════════════════');

            foreach ($stats['critical_failures'] as $failure) {
                $this->error("🔥 ID: {$failure['id']} | Job: {$failure['job_class']}");
                $this->error("   Failed: {$failure['failed_at']}");
                $this->error("   Error: {$failure['exception']}");
                $this->line('');
            }
        }
    }

    /**
     * Display alerts
     */
    private function displayAlerts(array $alerts): void
    {
        $this->warn('🚨 Job Monitoring Alerts');
        $this->warn('═══════════════════════════════════════');

        foreach ($alerts as $alert) {
            $icon = $alert['severity'] === 'critical' ? '🔥' : '⚠️';
            $this->warn("{$icon} {$alert['type']}: {$alert['message']}");
        }
        $this->line('');
    }

    /**
     * Send alerts to administrators
     */
    private function sendAlerts(array $alerts): void
    {
        try {
            $this->info('📧 Sending alerts to administrators...');

            // Get admin emails
            $adminEmails = MailListService::getAdminMailList();

            if (empty($adminEmails)) {
                $this->warn('⚠️ No admin emails configured - alerts not sent');

                return;
            }

            // Create a dummy user for notification
            $adminUser = new User(['email' => $adminEmails[0]]);

            // Send notification
            $adminUser->notify(new JobMonitoringAlert($alerts, $adminEmails));

            $this->info('✅ Alerts sent to '.count($adminEmails).' administrators');

        } catch (\Exception $e) {
            $this->error('❌ Failed to send alerts: '.$e->getMessage());
            Log::error('Failed to send job monitoring alerts', [
                'error' => $e->getMessage(),
                'alerts' => $alerts,
            ]);
        }
    }
}
