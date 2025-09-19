<?php

namespace App\Console\Commands;

use App\Mail\DailyReportMail;
use App\Services\BrevoApiService;
use App\Services\MailListService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send-daily {--email= : Override admin email address} {--mail-list : Send to all emails in mail list}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily report email to admin with platform statistics and metrics via Brevo API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting daily report generation...');

        try {
            // Get recipients - either single email or mail list
            $recipients = $this->getRecipients();

            if (empty($recipients)) {
                $this->error('❌ No recipients configured. Please set ADMIN_EMAIL or ADMIN_MAIL_LIST in your .env file or use --email option.');
                Log::error('Daily report failed: No recipients configured');

                return Command::FAILURE;
            }

            $this->info('📧 Preparing to send daily report to '.count($recipients).' recipient(s)');

            // Create the mailable to get the content
            $mailable = new DailyReportMail();

            $this->info('📊 Generating report data...');

            // Get the email content from the mailable
            $envelope = $mailable->envelope();
            $content = $mailable->content();

            // Generate HTML content from the view
            $htmlContent = view($content->view, $content->with)->render();

            // Generate text content (simplified version)
            $textContent = $this->generateTextContent($mailable);

            // Send via Brevo API to all recipients
            $brevoService = new BrevoApiService();
            $successCount = 0;
            $failureCount = 0;
            $messageIds = [];

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
                $successCount = count($recipients);
                $messageIds[] = $result['message_id'];
                $this->info('✅ Sent to '.count($recipients)." recipients - Message ID: {$result['message_id']}");
            } else {
                $failureCount = count($recipients);
                $this->error('❌ Failed to send to all recipients: '.($result['error'] ?? 'Unknown error'));
            }

            if ($successCount > 0) {
                $this->info("✅ Daily report sent successfully to {$successCount} recipient(s)");
                if ($failureCount > 0) {
                    $this->info("❌ Failed to send to {$failureCount} recipient(s)");
                }

                Log::info('Daily report sent successfully via Brevo API', [
                    'recipients' => $recipients,
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                    'message_ids' => $messageIds,
                    'timestamp' => now()->toISOString(),
                ]);

                return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;
            } else {
                $this->error('❌ Failed to send daily report to all recipients');

                Log::error('Daily report failed via Brevo API', [
                    'recipients' => $recipients,
                    'failure_count' => $failureCount,
                    'timestamp' => now()->toISOString(),
                ]);

                return Command::FAILURE;
            }

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

    /**
     * Generate text content for the email
     */
    private function generateTextContent(DailyReportMail $mailable): string
    {
        $data = $mailable->reportData;

        // Check if there's an error in the data
        if (isset($data['error'])) {
            return "
EGYAKIN Daily Report - {$data['date']}

═══════════════════════════════════════════════════════════════

❌ ERROR GENERATING REPORT DATA

{$data['error']}

═══════════════════════════════════════════════════════════════

Generated on: {$data['date']}
Report Period: {$data['period']}

Best regards,
EGYAKIN Development Team
            ";
        }

        return "
EGYAKIN Daily Report - {$data['date']}

═══════════════════════════════════════════════════════════════

📊 USER STATISTICS
• New Registrations: {$data['users']['new_registrations']}
• Total Users: {$data['users']['total_users']}
• Doctors: {$data['users']['doctors']}
• Regular Users: {$data['users']['regular_users']}
• Verified Users: {$data['users']['verified_users']}
• Blocked Users: {$data['users']['blocked_users']}

👥 PATIENT STATISTICS
• New Patients: {$data['patients']['new_patients']}
• Total Patients: {$data['patients']['total_patients']}
• Hidden Patients: {$data['patients']['hidden_patients']}
• Submitted Patients: {$data['patients']['submitted_patients']}
• Outcome Patients: {$data['patients']['outcome_patients']}

💬 CONSULTATION STATISTICS
• New Consultations: {$data['consultations']['new_consultations']}
• Pending Consultations: {$data['consultations']['pending_consultations']}
• Completed Consultations: {$data['consultations']['completed_consultations']}
• Open Consultations: {$data['consultations']['open_consultations']}
• AI Consultations: {$data['consultations']['ai_consultations']}
• New AI Consultations: {$data['consultations']['new_ai_consultations']}

📝 FEED ACTIVITY
• New Posts: {$data['feed']['new_posts']}
• Total Posts: {$data['feed']['total_posts']}
• Posts with Media: {$data['feed']['posts_with_media']}
• Group Posts: {$data['feed']['group_posts']}

👥 GROUP STATISTICS
• New Groups: {$data['groups']['new_groups']}
• Total Groups: {$data['groups']['total_groups']}
• Private Groups: {$data['groups']['private_groups']}
• Public Groups: {$data['groups']['public_groups']}

═══════════════════════════════════════════════════════════════

Generated on: {$data['date']}
Report Period: {$data['period']}

Best regards,
EGYAKIN Development Team
        ";
    }

    /**
     * Get recipients for daily report
     */
    private function getRecipients(): array
    {
        // If --email option is provided, use single email
        if ($this->option('email')) {
            return [$this->option('email')];
        }

        // If --mail-list option is provided, use admin mail list
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
