<?php

namespace App\Console\Commands;

use App\Mail\WeeklySummaryMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWeeklySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send-weekly {--email= : Override admin email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly summary email to admin with platform analytics and insights';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📈 Starting weekly summary generation...');

        try {
            // Get admin email from config or command option
            $adminEmail = $this->option('email') ?: config('mail.admin_email');

            if (empty($adminEmail)) {
                $this->error('❌ Admin email not configured. Please set ADMIN_EMAIL in your .env file or use --email option.');
                Log::error('Weekly summary failed: Admin email not configured');

                return Command::FAILURE;
            }

            $this->info("📧 Preparing to send weekly summary to: {$adminEmail}");

            // Create and send the mailable
            $mailable = new WeeklySummaryMail();

            $this->info('📊 Generating summary data and insights...');

            Mail::to($adminEmail)->send($mailable);

            $this->info("✅ Weekly summary sent successfully to {$adminEmail}");

            Log::info('Weekly summary sent successfully', [
                'recipient' => $adminEmail,
                'timestamp' => now()->toISOString(),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to send weekly summary: '.$e->getMessage());

            Log::error('Weekly summary failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString(),
            ]);

            return Command::FAILURE;
        }
    }
}
