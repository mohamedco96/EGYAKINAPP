<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class AppleSecretScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apple:schedule-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and auto-renew Apple Client Secrets for all environments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Apple Client Secret Scheduler');
        $this->info('============================');

        $environments = ['dev', 'staging', 'prod'];
        $renewedCount = 0;

        foreach ($environments as $env) {
            $this->line("Checking {$env} environment...");

            try {
                $exitCode = Artisan::call('apple:manage-secret', [
                    'action' => 'check',
                    '--env' => $env,
                    '--auto-renew' => true,
                ]);

                if ($exitCode === 0) {
                    $this->info("✅ {$env} environment checked successfully");
                    $renewedCount++;
                } else {
                    $this->warn("⚠️  {$env} environment had issues");
                }
            } catch (\Exception $e) {
                $this->error("❌ Error checking {$env}: ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->info('Scheduler completed. Checked '.count($environments).' environments.');

        if ($renewedCount > 0) {
            $this->info("Renewed {$renewedCount} client secret(s).");
        }
    }
}
