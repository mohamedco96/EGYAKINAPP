<?php

namespace App\Console\Commands;

use App\Mail\WeeklySummaryMail;
use App\Services\BrevoApiService;
use App\Services\MailListService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendWeeklySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send-weekly {--email= : Override admin email address} {--mail-list : Send to all emails in mail list}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly summary email to admin with platform analytics and insights via Brevo API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📈 Starting weekly summary generation...');

        try {
            // Get recipients - either single email or mail list
            $recipients = $this->getRecipients();

            if (empty($recipients)) {
                $this->error('❌ No recipients configured. Please set ADMIN_EMAIL or ADMIN_MAIL_LIST in your .env file or use --email option.');
                Log::error('Weekly summary failed: No recipients configured');

                return Command::FAILURE;
            }

            $this->info('📧 Preparing to send weekly summary to '.count($recipients).' recipient(s)');

            // Create the mailable to get the content
            $mailable = new WeeklySummaryMail();

            $this->info('📊 Generating summary data and insights...');

            // Get the email content from the mailable
            $envelope = $mailable->envelope();
            $content = $mailable->content();

            // Generate HTML content from the view
            $htmlContent = view($content->view, $content->with)->render();

            // Generate text content (simplified version)
            $textContent = $this->generateTextContent($mailable);

            // Send via Brevo API
            $brevoService = new BrevoApiService();

            $this->info('📡 Sending via Brevo API...');
            $this->info('📧 Recipients: '.implode(', ', $recipients));

            // Send one email with all recipients
            $result = $brevoService->sendEmailToMultipleRecipients(
                $recipients,
                $envelope->subject,
                $htmlContent,
                $textContent,
                [
                    'name' => config('mail.from.name'),
                    'email' => config('mail.from.address'),
                ]
            );

            if ($result['success']) {
                $this->info('✅ Weekly summary sent successfully to '.count($recipients).' recipients');
                $this->info("📧 Message ID: {$result['message_id']}");

                Log::info('Weekly summary sent successfully via Brevo API', [
                    'recipients' => $recipients,
                    'message_id' => $result['message_id'],
                    'timestamp' => now()->toISOString(),
                ]);

                return Command::SUCCESS;
            } else {
                $this->error('❌ Brevo API failed to send weekly summary: '.($result['error'] ?? 'Unknown error'));

                Log::error('Weekly summary failed via Brevo API', [
                    'recipients' => $recipients,
                    'error' => $result['error'] ?? 'Unknown error',
                    'timestamp' => now()->toISOString(),
                ]);

                return Command::FAILURE;
            }

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

    /**
     * Generate text content for the email
     */
    private function generateTextContent(WeeklySummaryMail $mailable): string
    {
        $data = $mailable->summaryData;

        // Check if there's an error in the data
        if (isset($data['error'])) {
            return "
EGYAKIN Weekly Summary - {$data['week_period']}

═══════════════════════════════════════════════════════════════

❌ ERROR GENERATING SUMMARY DATA

{$data['error']}

═══════════════════════════════════════════════════════════════

Generated on: {$data['week_period']}

Best regards,
EGYAKIN Development Team
            ";
        }

        return "
EGYAKIN Weekly Summary - {$data['week_period']}

═══════════════════════════════════════════════════════════════

📊 CURRENT WEEK STATISTICS
• New Users: {$data['current_week']['new_users']}
• New Doctors: {$data['current_week']['new_doctors']}
• New Patients: {$data['current_week']['new_patients']}
• New Consultations: {$data['current_week']['new_consultations']}
• New AI Consultations: {$data['current_week']['new_ai_consultations']}
• New Posts: {$data['current_week']['new_posts']}
• New Groups: {$data['current_week']['new_groups']}
• Total Likes: {$data['current_week']['total_likes']}
• Total Comments: {$data['current_week']['total_comments']}

📈 GROWTH COMPARISON (vs Last Week)
• Users: {$data['growth']['new_users']}%
• Doctors: {$data['growth']['new_doctors']}%
• Patients: {$data['growth']['new_patients']}%
• Consultations: {$data['growth']['new_consultations']}%
• AI Consultations: {$data['growth']['new_ai_consultations']}%
• Posts: {$data['growth']['new_posts']}%
• Groups: {$data['growth']['new_groups']}%

🏆 TOP PERFORMERS THIS WEEK
• Most Active Doctors: ".count($data['top_performers']['most_active_doctors']).' doctors
• Doctors with Patients: '.count($data['top_performers']['doctors_with_patients']).' doctors
• Doctors with Posts: '.count($data['top_performers']['doctors_with_posts']).' doctors
• Popular Posts: '.count($data['top_performers']['popular_posts']).' posts
• Active Groups: '.count($data['top_performers']['active_groups'])." groups

📊 SYSTEM OVERVIEW
• Total Users: {$data['system_overview']['total_users']}
• Total Doctors: {$data['system_overview']['total_doctors']}
• Total Patients: {$data['system_overview']['total_patients']}
• Total Consultations: {$data['system_overview']['total_consultations']}
• Total AI Consultations: {$data['system_overview']['total_ai_consultations']}
• Total Posts: {$data['system_overview']['total_posts']}
• Total Groups: {$data['system_overview']['total_groups']}

═══════════════════════════════════════════════════════════════

Generated on: {$data['week_period']}

Best regards,
EGYAKIN Development Team
        ";
    }

    /**
     * Get recipients for weekly summary
     */
    private function getRecipients(): array
    {
        // If --email option is provided, use single email
        if ($this->option('email')) {
            return [$this->option('email')];
        }

        // If --mail-list option is provided, use mail list
        if ($this->option('mail-list')) {
            $recipients = MailListService::getAdminMailList();
            if (empty($recipients)) {
                $this->error('❌ Admin mail list not configured. Please set ADMIN_MAIL_LIST in your .env file.');

                return [];
            }

            return $recipients;
        }

        // Default: use admin email
        $adminEmail = config('mail.admin_email');
        if (empty($adminEmail)) {
            $this->error('❌ Admin email not configured. Please set ADMIN_EMAIL in your .env file.');

            return [];
        }

        return [$adminEmail];
    }
}
