<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "=== Production Email Test ===\n\n";

// Test 1: Check configuration
echo "1. Current Mail Configuration:\n";
echo 'MAIL_MAILER: '.config('mail.default')."\n";
echo 'MAIL_HOST: '.config('mail.mailers.smtp.host')."\n";
echo 'MAIL_PORT: '.config('mail.mailers.smtp.port')."\n";
echo 'MAIL_USERNAME: '.config('mail.mailers.smtp.username')."\n";
echo 'MAIL_ENCRYPTION: '.config('mail.mailers.smtp.encryption')."\n";
echo 'MAIL_FROM_ADDRESS: '.config('mail.from.address')."\n";
echo 'ADMIN_EMAIL: '.config('mail.admin_email')."\n\n";

// Test 2: Send test email
echo "2. Sending Test Email:\n";
try {
    Mail::raw('This is a test email from EGYAKIN production system. Sent at: '.now()->format('Y-m-d H:i:s'), function ($message) {
        $message->to('mohamedco215@gmail.com')
            ->subject('EGYAKIN Production Email Test - '.now()->format('Y-m-d H:i:s'));
    });

    echo "✅ Email sent successfully!\n";
    echo "📧 Check your inbox (and spam folder) for: mohamedco215@gmail.com\n\n";

} catch (Exception $e) {
    echo "❌ Email failed to send!\n";
    echo 'Error: '.$e->getMessage()."\n\n";
}

// Test 3: Test daily report
echo "3. Testing Daily Report:\n";
try {
    $output = shell_exec('php artisan reports:send-daily --email=mohamedco215@gmail.com 2>&1');
    echo $output."\n";
} catch (Exception $e) {
    echo '❌ Daily report failed: '.$e->getMessage()."\n\n";
}

echo "=== Test Complete ===\n";
