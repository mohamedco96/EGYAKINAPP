<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "=== Email Configuration Test ===\n\n";

// Test 1: Check mail configuration
echo "1. Mail Configuration:\n";
echo 'MAIL_MAILER: '.config('mail.default')."\n";
echo 'MAIL_HOST: '.config('mail.mailers.smtp.host')."\n";
echo 'MAIL_PORT: '.config('mail.mailers.smtp.port')."\n";
echo 'MAIL_USERNAME: '.config('mail.mailers.smtp.username')."\n";
echo 'MAIL_ENCRYPTION: '.config('mail.mailers.smtp.encryption')."\n";
echo 'MAIL_FROM_ADDRESS: '.config('mail.from.address')."\n";
echo 'MAIL_FROM_NAME: '.config('mail.from.name')."\n";
echo 'ADMIN_EMAIL: '.config('mail.admin_email')."\n\n";

// Test 2: Test SMTP connection
echo "2. Testing SMTP Connection:\n";
try {
    $transport = new \Swift_SmtpTransport(
        config('mail.mailers.smtp.host'),
        config('mail.mailers.smtp.port'),
        config('mail.mailers.smtp.encryption')
    );

    $transport->setUsername(config('mail.mailers.smtp.username'));
    $transport->setPassword(config('mail.mailers.smtp.password'));

    $mailer = new \Swift_Mailer($transport);
    $result = $mailer->getTransport()->start();

    echo "✅ SMTP Connection: SUCCESS\n";
    echo 'Server Response: '.$result."\n\n";

} catch (Exception $e) {
    echo "❌ SMTP Connection: FAILED\n";
    echo 'Error: '.$e->getMessage()."\n\n";
}

// Test 3: Test sending a simple email
echo "3. Testing Email Sending:\n";
try {
    Mail::raw('This is a test email from EGYAKIN system.', function ($message) {
        $message->to('mohamedco215@gmail.com')
            ->subject('EGYAKIN Email Test - '.now()->format('Y-m-d H:i:s'));
    });

    echo "✅ Email Sent: SUCCESS\n";
    echo "Check your inbox for test email\n\n";

} catch (Exception $e) {
    echo "❌ Email Sent: FAILED\n";
    echo 'Error: '.$e->getMessage()."\n\n";
}

// Test 4: Check logs
echo "4. Recent Email Logs:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $emailLogs = preg_grep('/mail|email|smtp/i', explode("\n", $logs));
    $recentLogs = array_slice($emailLogs, -10);

    foreach ($recentLogs as $log) {
        echo $log."\n";
    }
} else {
    echo "No log file found\n";
}

echo "\n=== Test Complete ===\n";
