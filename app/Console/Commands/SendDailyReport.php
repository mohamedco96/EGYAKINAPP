<?php

namespace App\Console\Commands;

use App\Mail\DailyReportMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send-daily {--email= : Override admin email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily report email to admin with platform statistics and metrics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting daily report generation...');

        try {
            // Get admin email from config or command option
            $adminEmail = $this->option('email') ?: config('mail.admin_email');

            if (empty($adminEmail)) {
                $this->error('❌ Admin email not configured. Please set ADMIN_EMAIL in your .env file or use --email option.');
                Log::error('Daily report failed: Admin email not configured');

                return Command::FAILURE;
            }

            $this->info("📧 Preparing to send daily report to: {$adminEmail}");

            // Create and send the mailable
            $mailable = new DailyReportMail();

            $this->info('📊 Generating report data...');

            Mail::to($adminEmail)->send($mailable);

            $this->info("✅ Daily report sent successfully to {$adminEmail}");

            Log::info('Daily report sent successfully', [
                'recipient' => $adminEmail,
                'timestamp' => now()->toISOString(),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to send daily report: '.$e->getMessage());

            Log::error('Daily report failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString(),
            ]);

            return Command::FAILURE;
        }
    }
}
