<?php

namespace App\Console\Commands;

use App\Mail\BrevoApiMail;
use App\Mail\DailyReportMail;
use App\Mail\TestMail;
use App\Mail\VerifyEmail;
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

        } catch (\Exception $e) {
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

        $this->newLine();
    }

    /**
     * Send simple test email
     */
    private function sendSimpleTest($email, $subject = null, $body = null)
    {
        $subject = $subject ?? 'EGYAKIN Mail Test - '.now()->format('Y-m-d H:i:s');
        $body = $body ?? $this->getDefaultTestBody();

        $testMail = new TestMail($subject, $body);
        Mail::to($email)->send($testMail);
    }

    /**
     * Send daily report test email
     */
    private function sendDailyReportTest($email)
    {
        $dailyReport = new DailyReportMail();

        Mail::to($email)->send($dailyReport);
    }

    /**
     * Send verify email test
     */
    private function sendVerifyEmailTest($email)
    {
        $verificationUrl = url('/verify-email?token=test-token-'.time());
        $verifyEmail = new VerifyEmail($verificationUrl);

        Mail::to($email)->send($verifyEmail);
    }

    /**
     * Send Brevo API test email
     */
    private function sendBrevoApiTest($email, $subject = null, $body = null)
    {
        $subject = $subject ?? 'EGYAKIN Mail Test (Brevo API) - '.now()->format('Y-m-d H:i:s');
        $htmlContent = BrevoApiMail::getDefaultTestHtmlContent();
        $textContent = BrevoApiMail::getDefaultTestTextContent();

        $brevoMail = new BrevoApiMail($email, $subject, $htmlContent, $textContent);
        $result = $brevoMail->sendViaBrevoApi();

        if (! $result['success']) {
            throw new \Exception('Brevo API Error: '.($result['error'] ?? 'Unknown error'));
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
}
