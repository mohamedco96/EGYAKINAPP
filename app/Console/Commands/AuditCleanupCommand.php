<?php

namespace App\Console\Commands;

use App\Services\AuditService;
use Illuminate\Console\Command;

class AuditCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:cleanup {--days=90 : Number of days to keep audit logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old audit logs to maintain database performance';

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

        $this->info("Starting audit log cleanup (keeping logs from last {$days} days)...");

        $deletedCount = $this->auditService->cleanupOldLogs($days);

        $this->info("Cleanup completed. Deleted {$deletedCount} old audit log(s).");

        return Command::SUCCESS;
    }
}
