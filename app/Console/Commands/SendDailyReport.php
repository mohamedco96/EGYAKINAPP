<?php

namespace App\Console\Commands;

use App\Mail\DailyReportMail;
use App\Services\BrevoApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Send daily report email to admin with platform statistics and metrics via Brevo API';

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

            // Send via Brevo API
            $brevoService = new BrevoApiService();

            $this->info('📡 Sending via Brevo API...');

            $result = $brevoService->sendEmail(
                $adminEmail,
                $envelope->subject,
                $htmlContent,
                $textContent,
                [
                    'name' => config('mail.from.name'),
                    'email' => config('mail.from.address'),
                ]
            );

            if ($result['success']) {
                $this->info("✅ Daily report sent successfully to {$adminEmail}");
                $this->info("📧 Message ID: {$result['message_id']}");

                Log::info('Daily report sent successfully via Brevo API', [
                    'recipient' => $adminEmail,
                    'message_id' => $result['message_id'],
                    'timestamp' => now()->toISOString(),
                ]);

                return Command::SUCCESS;
            } else {
                $this->error('❌ Brevo API failed to send daily report: '.($result['error'] ?? 'Unknown error'));

                Log::error('Daily report failed via Brevo API', [
                    'recipient' => $adminEmail,
                    'error' => $result['error'] ?? 'Unknown error',
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
}
