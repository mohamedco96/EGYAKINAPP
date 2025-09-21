<?php

namespace App\Console\Commands;

use App\Services\AuditService;
use Illuminate\Console\Command;

class AuditStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:stats {--days=30 : Number of days to analyze}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display audit log statistics and insights';

    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        parent::__construct();
        $this->auditService = $auditService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');

        $this->info("Audit Log Statistics (Last {$days} days)");
        $this->line(str_repeat('=', 50));

        $stats = $this->auditService->getAuditStats($days);

        // Total logs
        $this->info("Total Audit Logs: {$stats['total_logs']}");
        $this->newLine();

        // Event types breakdown
        if (! empty($stats['event_types'])) {
            $this->info('Event Types:');
            $headers = ['Event Type', 'Count', 'Percentage'];
            $rows = [];

            foreach ($stats['event_types'] as $eventType => $count) {
                $percentage = $stats['total_logs'] > 0 ? round(($count / $stats['total_logs']) * 100, 2) : 0;
                $rows[] = [$eventType, $count, $percentage.'%'];
            }

            $this->table($headers, $rows);
            $this->newLine();
        }

        // Top users
        if (! empty($stats['top_users'])) {
            $this->info('Most Active Users:');
            $headers = ['User', 'Email', 'Actions'];
            $rows = [];

            foreach (array_slice($stats['top_users'], 0, 10) as $user) {
                $rows[] = [
                    $user['user_name'] ?? 'Unknown',
                    $user['user_email'] ?? 'N/A',
                    $user['count'],
                ];
            }

            $this->table($headers, $rows);
        }

        return Command::SUCCESS;
    }
}
