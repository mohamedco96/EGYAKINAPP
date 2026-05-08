<?php

namespace App\Console\Commands;

use App\Mail\BrevoApiMail;
use App\Mail\BrevoMail;
use App\Mail\DailyReportMail;
use App\Mail\VerifyEmail;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test 
                            {email? : Email address to send test email to}
                            {--type=simple : Type of test email (simple, daily-report, verify-email, brevo-api)}
                            {--subject= : Custom subject for the email}
                            {--body= : Custom body for the email}
                            {--api : Use Brevo API instead of SMTP}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email functionality by sending a test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Email Test...');
        $this->newLine();

        // Display current mail configuration
        $this->displayMailConfiguration();

        // Get email address
        $email = $this->argument('email') ?? $this->ask('Enter email address to send test email to');

        if (! $email) {
            $this->error('❌ Email address is required!');

            return 1;
        }

        // Validate email format
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('❌ Invalid email address format!');

            return 1;
        }

        $type = $this->option('type');
        $subject = $this->option('subject');
        $body = $this->option('body');
        $useApi = $this->option('api');

        // Override type if API flag is used
        if ($useApi) {
            $type = 'brevo-api';
        }

        $this->info("📧 Sending {$type} test email to: {$email}");
        $this->newLine();

        try {
            switch ($type) {
                case 'brevo-api':
                    $this->sendBrevoApiTest($email, $subject, $body);
                    break;
                case 'daily-report':
                    $this->sendDailyReportTest($email);
                    break;
                case 'verify-email':
                    $this->sendVerifyEmailTest($email);
                    break;
                case 'simple':
                default:
                    $this->sendSimpleTest($email, $subject, $body);
                    break;
            }

            $this->info('✅ Email sent successfully!');
            $this->newLine();
            $this->info('📋 Test Summary:');
            $this->line("   • Email Type: {$type}");
            $this->line("   • Recipient: {$email}");
            $this->line('   • Mail Driver: '.config('mail.default'));
            $this->line('   • From Address: '.config('mail.from.address'));
            $this->line('   • From Name: '.config('mail.from.name'));

            return 0;

        } catch (Exception $e) {
            $this->error('❌ Failed to send email!');
            $this->error('Error: '.$e->getMessage());

            // Log the error
            Log::error('Mail test failed', [
                'email' => $email,
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Display current mail configuration
     */
    private function displayMailConfiguration()
    {
        $this->info('📋 Current Mail Configuration:');
        $this->line('   • Default Mailer: '.config('mail.default'));
        $this->line('   • From Address: '.config('mail.from.address'));
        $this->line('   • From Name: '.config('mail.from.name'));
        $this->line('   • Admin Email: '.config('mail.admin_email'));

        if (config('mail.default') === 'smtp') {
            $this->line('   • SMTP Host: '.config('mail.mailers.smtp.host'));
            $this->line('   • SMTP Port: '.config('mail.mailers.smtp.port'));
            $this->line('   • SMTP Encryption: '.config('mail.mailers.smtp.encryption'));
            $this->line('   • SMTP Username: '.(config('mail.mailers.smtp.username') ? '***configured***' : 'not set'));
        }

        // Show Brevo API configuration
        if (config('services.brevo.api_key')) {
            $this->line('   • Brevo API Key: ***configured***');
        } else {
            $this->line('   • Brevo API Key: not set');
        }

        // Show current mailer info
        if (config('mail.default') === 'brevo-api') {
            $this->line('   • Default Mailer: Brevo API (Recommended for GoDaddy)');
        }

        $this->newLine();
    }

    /**
     * Send simple test email
     */
    private function sendSimpleTest($email, $subject = null, $body = null)
    {
        $subject = $subject ?? 'EGYAKIN Mail Test - '.now()->format('Y-m-d H:i:s');
        $body = $body ?? $this->getDefaultTestBody();

        // Use Brevo API for simple test
        $htmlContent = $this->getDefaultTestHtmlContent($subject, $body);
        $textContent = $body;

        $brevoMail = new BrevoMail($email, $subject, $htmlContent, $textContent);
        $result = $brevoMail->sendViaBrevoApi();

        if (! $result['success']) {
            throw new Exception('Brevo API Error: '.($result['error'] ?? 'Unknown error'));
        }

        $this->info('📡 Brevo API Response:');
        $this->line('   • Message ID: '.($result['message_id'] ?? 'N/A'));
    }

    /**
     * Send daily report test email
     */
    private function sendDailyReportTest($email)
    {
        $dailyReport = new DailyReportMail;

        // Get the email content from the mailable
        $envelope = $dailyReport->envelope();
        $content = $dailyReport->content();

        // Generate HTML content from the view
        $htmlContent = view($content->view, $content->with)->render();

        // Generate text content (simplified version)
        $textContent = $this->getDailyReportTextContent($dailyReport);

        $brevoMail = new BrevoMail($email, $envelope->subject, $htmlContent, $textContent);
        $result = $brevoMail->sendViaBrevoApi();

        if (! $result['success']) {
            throw new Exception('Brevo API Error: '.($result['error'] ?? 'Unknown error'));
        }

        $this->info('📡 Brevo API Response:');
        $this->line('   • Message ID: '.($result['message_id'] ?? 'N/A'));
    }

    /**
     * Send verify email test
     */
    private function sendVerifyEmailTest($email)
    {
        $verificationUrl = url('/verify-email?token=test-token-'.time());
        $verifyEmail = new VerifyEmail($verificationUrl);

        // Get the email content from the mailable
        $envelope = $verifyEmail->envelope();
        $content = $verifyEmail->content();

        // Generate HTML content from the view
        $htmlContent = view($content->view, $content->with)->render();

        // Generate text content (simplified version)
        $textContent = $this->getVerifyEmailTextContent($verificationUrl);

        $brevoMail = new BrevoMail($email, $envelope->subject, $htmlContent, $textContent);
        $result = $brevoMail->sendViaBrevoApi();

        if (! $result['success']) {
            throw new Exception('Brevo API Error: '.($result['error'] ?? 'Unknown error'));
        }

        $this->info('📡 Brevo API Response:');
        $this->line('   • Message ID: '.($result['message_id'] ?? 'N/A'));
    }

    /**
     * Send Brevo API test email
     */
    private function sendBrevoApiTest($email, $subject = null, $body = null)
    {
        $subject = $subject ?? 'EGYAKIN Mail Test (Brevo API) - '.now()->format('Y-m-d H:i:s');
        $htmlContent = BrevoApiMail::getDefaultTestHtmlContent();
        $textContent = BrevoApiMail::getDefaultTestTextContent();

        // Use the simpler BrevoMail class
        $brevoMail = new BrevoMail($email, $subject, $htmlContent, $textContent);
        $result = $brevoMail->sendViaBrevoApi();

        if (! $result['success']) {
            throw new Exception('Brevo API Error: '.($result['error'] ?? 'Unknown error'));
        }

        $this->info('📡 Brevo API Response:');
        $this->line('   • Message ID: '.($result['message_id'] ?? 'N/A'));
    }

    /**
     * Get default test email body
     */
    private function getDefaultTestBody()
    {
        return '
Hello!

This is a test email from EGYAKIN application.

Test Details:
• Sent at: '.now()->format('Y-m-d H:i:s').'
• Mail Driver: '.config('mail.default').'
• Application: EGYAKIN
• Environment: '.app()->environment().'

If you received this email, your mail configuration is working correctly!

Best regards,
EGYAKIN Team
        ';
    }

    /**
     * Get default test HTML content
     */
    private function getDefaultTestHtmlContent($subject, $body)
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>'.$subject.'</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 8px 8px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>🏥 EGYAKIN Mail Test</h1>
            </div>
            <div class="content">
                '.nl2br(htmlspecialchars($body)).'
            </div>
            <div class="footer">
                <p>Best regards,<br><strong>EGYAKIN Development Team</strong></p>
            </div>
        </body>
        </html>';
    }

    /**
     * Get daily report text content
     */
    private function getDailyReportTextContent($dailyReport)
    {
        $data = $dailyReport->reportData;

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
     * Get verify email text content
     */
    private function getVerifyEmailTextContent($verificationUrl)
    {
        return "
EGYAKIN Email Verification

Hello!

Please verify your email address by clicking the link below:

{$verificationUrl}

If you did not create an account with EGYAKIN, please ignore this email.

Best regards,
EGYAKIN Development Team
        ";
    }
}
