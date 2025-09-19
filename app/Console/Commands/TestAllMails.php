<?php

namespace App\Console\Commands;

use App\Mail\DailyReportMail;
use App\Mail\TestMail;
use App\Mail\VerifyEmail;
use App\Mail\WeeklySummaryMail;
use App\Models\User;
use App\Notifications\ContactRequestNotification;
use App\Notifications\EmailVerificationNotification;
use App\Notifications\ReachingSpecificPoints;
use App\Notifications\ReminderNotification;
use App\Notifications\ResetPasswordVerificationNotification;
use App\Notifications\WelcomeMailNotification;
use App\Services\BrevoApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class TestAllMails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-all 
                            {email : Email address to send test emails to}
                            {--type=all : Type of mail to test (all, mailable, notification, specific)}
                            {--specific= : Specific mail class to test}
                            {--brevo : Use Brevo API for sending}
                            {--dry-run : Validate templates without sending emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all mail templates and notifications in the EGYAKIN project';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $type = $this->option('type');
        $specific = $this->option('specific');
        $useBrevo = $this->option('brevo');
        $dryRun = $this->option('dry-run');

        $this->info('🚀 Starting EGYAKIN Mail Template Testing');
        $this->info("📧 Testing email: {$email}");
        $this->info("🔧 Type: {$type}");
        $this->info('📡 Method: '.($useBrevo ? 'Brevo API' : 'Laravel Mail'));
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE: Templates will be validated but NO emails will be sent');
        }

        // Create a test user
        $testUser = $this->createTestUser($email);

        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];

        switch ($type) {
            case 'all':
                $results = $this->testAllMails($testUser, $useBrevo, $dryRun);
                break;
            case 'mailable':
                $results = $this->testMailableClasses($testUser, $useBrevo, $dryRun);
                break;
            case 'notification':
                $results = $this->testNotificationClasses($testUser, $useBrevo, $dryRun);
                break;
            case 'specific':
                if (! $specific) {
                    $this->error('❌ Please specify --specific=ClassName for specific testing');

                    return Command::FAILURE;
                }
                $results = $this->testSpecificMail($testUser, $specific, $useBrevo, $dryRun);
                break;
            default:
                $this->error('❌ Invalid type. Use: all, mailable, notification, or specific');

                return Command::FAILURE;
        }

        $this->displayResults($results);

        return $results['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Create a test user for testing
     */
    private function createTestUser(string $email): User
    {
        return new User([
            'id' => 999,
            'name' => 'Test User',
            'email' => $email,
            'lname' => 'Testing',
            'specialty' => 'General Medicine',
            'workingplace' => 'Test Hospital',
            'phone' => '+1234567890',
            'job' => 'Doctor',
            'highestdegree' => 'MD',
            'registration_number' => 'TEST123',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test all mail types
     */
    private function testAllMails(User $testUser, bool $useBrevo, bool $dryRun = false): array
    {
        $results = ['success' => 0, 'failed' => 0, 'details' => []];

        $this->info('📋 Testing all mail templates...');

        // Test Mailable classes
        $mailableResults = $this->testMailableClasses($testUser, $useBrevo, $dryRun);
        $results['success'] += $mailableResults['success'];
        $results['failed'] += $mailableResults['failed'];
        $results['details'] = array_merge($results['details'], $mailableResults['details']);

        // Test Notification classes
        $notificationResults = $this->testNotificationClasses($testUser, $useBrevo, $dryRun);
        $results['success'] += $notificationResults['success'];
        $results['failed'] += $notificationResults['failed'];
        $results['details'] = array_merge($results['details'], $notificationResults['details']);

        return $results;
    }

    /**
     * Test Mailable classes
     */
    private function testMailableClasses(User $testUser, bool $useBrevo, bool $dryRun = false): array
    {
        $results = ['success' => 0, 'failed' => 0, 'details' => []];

        $this->info('📧 Testing Mailable Classes...');

        $mailableClasses = [
            'DailyReportMail' => DailyReportMail::class,
            'WeeklySummaryMail' => WeeklySummaryMail::class,
            'TestMail' => TestMail::class,
            'VerifyEmail' => VerifyEmail::class,
        ];

        foreach ($mailableClasses as $name => $class) {
            try {
                $this->info("  📤 Testing {$name}...");

                if ($dryRun) {
                    $result = $this->validateMailable($class, $testUser);
                } elseif ($useBrevo) {
                    $result = $this->sendViaBrevo($class, $testUser);
                } else {
                    $result = $this->sendViaLaravel($class, $testUser);
                }

                if ($result['success']) {
                    $results['success']++;
                    $results['details'][] = [
                        'type' => 'Mailable',
                        'name' => $name,
                        'status' => '✅ Success',
                        'message_id' => $result['message_id'] ?? 'N/A',
                        'method' => $useBrevo ? 'Brevo API' : 'Laravel Mail',
                    ];
                } else {
                    $results['failed']++;
                    $results['details'][] = [
                        'type' => 'Mailable',
                        'name' => $name,
                        'status' => '❌ Failed',
                        'error' => $result['error'] ?? 'Unknown error',
                        'method' => $useBrevo ? 'Brevo API' : 'Laravel Mail',
                    ];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'type' => 'Mailable',
                    'name' => $name,
                    'status' => '❌ Exception',
                    'error' => $e->getMessage(),
                    'method' => $useBrevo ? 'Brevo API' : 'Laravel Mail',
                ];
            }
        }

        return $results;
    }

    /**
     * Test Notification classes
     */
    private function testNotificationClasses(User $testUser, bool $useBrevo, bool $dryRun = false): array
    {
        $results = ['success' => 0, 'failed' => 0, 'details' => []];

        $this->info('🔔 Testing Notification Classes...');

        $notificationClasses = [
            'WelcomeMailNotification' => WelcomeMailNotification::class,
            'EmailVerificationNotification' => EmailVerificationNotification::class,
            'ResetPasswordVerificationNotification' => ResetPasswordVerificationNotification::class,
            'ReminderNotification' => ReminderNotification::class,
            'ReachingSpecificPoints' => ReachingSpecificPoints::class,
            'ContactRequestNotification' => ContactRequestNotification::class,
        ];

        foreach ($notificationClasses as $name => $class) {
            try {
                $this->info("  📤 Testing {$name}...");

                if ($dryRun) {
                    $result = $this->validateNotification($class, $testUser);
                } elseif ($useBrevo) {
                    $result = $this->sendNotificationViaBrevo($class, $testUser);
                } else {
                    $result = $this->sendNotificationViaLaravel($class, $testUser);
                }

                if ($result['success']) {
                    $results['success']++;
                    $results['details'][] = [
                        'type' => 'Notification',
                        'name' => $name,
                        'status' => '✅ Success',
                        'message_id' => $result['message_id'] ?? 'N/A',
                        'method' => $useBrevo ? 'Brevo API' : 'Laravel Mail',
                    ];
                } else {
                    $results['failed']++;
                    $results['details'][] = [
                        'type' => 'Notification',
                        'name' => $name,
                        'status' => '❌ Failed',
                        'error' => $result['error'] ?? 'Unknown error',
                        'method' => $useBrevo ? 'Brevo API' : 'Laravel Mail',
                    ];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'type' => 'Notification',
                    'name' => $name,
                    'status' => '❌ Exception',
                    'error' => $e->getMessage(),
                    'method' => $useBrevo ? 'Brevo API' : 'Laravel Mail',
                ];
            }
        }

        return $results;
    }

    /**
     * Test specific mail class
     */
    private function testSpecificMail(User $testUser, string $className, bool $useBrevo, bool $dryRun = false): array
    {
        $results = ['success' => 0, 'failed' => 0, 'details' => []];

        $this->info("🎯 Testing specific mail class: {$className}");

        // Try to find the class in both Mail and Notifications namespaces
        $mailableClassName = "App\\Mail\\{$className}";
        $notificationClassName = "App\\Notifications\\{$className}";

        $fullClassName = null;
        $classType = null;

        if (class_exists($mailableClassName)) {
            $fullClassName = $mailableClassName;
            $classType = 'mailable';
        } elseif (class_exists($notificationClassName)) {
            $fullClassName = $notificationClassName;
            $classType = 'notification';
        } else {
            $this->error("❌ Class {$className} not found in App\\Mail\\ or App\\Notifications\\");

            return $results;
        }

        try {
            if ($classType === 'mailable') {
                if ($dryRun) {
                    $result = $this->validateMailable($fullClassName, $testUser);
                } elseif ($useBrevo) {
                    $result = $this->sendViaBrevo($fullClassName, $testUser);
                } else {
                    $result = $this->sendViaLaravel($fullClassName, $testUser);
                }
            } else {
                if ($dryRun) {
                    $result = $this->validateNotification($fullClassName, $testUser);
                } elseif ($useBrevo) {
                    $result = $this->sendNotificationViaBrevo($fullClassName, $testUser);
                } else {
                    $result = $this->sendNotificationViaLaravel($fullClassName, $testUser);
                }
            }

            if ($result['success']) {
                $results['success']++;
                $results['details'][] = [
                    'type' => 'Specific',
                    'name' => $className,
                    'status' => '✅ Success',
                    'message_id' => $result['message_id'] ?? 'N/A',
                    'method' => $useBrevo ? 'Brevo API' : 'Laravel Mail',
                ];
            } else {
                $results['failed']++;
                $results['details'][] = [
                    'type' => 'Specific',
                    'name' => $className,
                    'status' => '❌ Failed',
                    'error' => $result['error'] ?? 'Unknown error',
                    'method' => $useBrevo ? 'Brevo API' : 'Laravel Mail',
                ];
            }
        } catch (\Exception $e) {
            $results['failed']++;
            $results['details'][] = [
                'type' => 'Specific',
                'name' => $className,
                'status' => '❌ Exception',
                'error' => $e->getMessage(),
                'method' => $useBrevo ? 'Brevo API' : 'Laravel Mail',
            ];
        }

        return $results;
    }

    /**
     * Send mailable via Brevo API
     */
    private function sendViaBrevo(string $mailableClass, User $testUser): array
    {
        try {
            // Handle VerifyEmail class which requires a verification URL
            if ($mailableClass === VerifyEmail::class) {
                $mailable = new VerifyEmail('https://test.egyakin.com/verify?token=test123');
            } else {
                $mailable = new $mailableClass();
            }

            $envelope = $mailable->envelope();
            $content = $mailable->content();

            $htmlContent = view($content->view, array_merge($content->with, ['data' => $mailable->reportData ?? []]))->render();

            $brevoService = new BrevoApiService();

            $result = $brevoService->sendEmail(
                $testUser->email,
                $envelope->subject,
                $htmlContent,
                'Text version not available',
                [
                    'name' => config('mail.from.name'),
                    'email' => config('mail.from.address'),
                ]
            );

            return [
                'success' => $result['success'],
                'message_id' => $result['message_id'] ?? null,
                'error' => $result['error'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send mailable via Laravel Mail
     */
    private function sendViaLaravel(string $mailableClass, User $testUser): array
    {
        try {
            // Handle VerifyEmail class which requires a verification URL
            if ($mailableClass === VerifyEmail::class) {
                $mailable = new VerifyEmail('https://test.egyakin.com/verify?token=test123');
            } else {
                $mailable = new $mailableClass();
            }

            Mail::to($testUser->email)->send($mailable);

            return [
                'success' => true,
                'message_id' => 'Laravel-Mail-'.now()->timestamp,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send notification via Brevo API
     */
    private function sendNotificationViaBrevo(string $notificationClass, User $testUser): array
    {
        try {
            // Handle notifications that require constructor parameters
            if ($notificationClass === 'App\\Notifications\\ReminderNotification') {
                // Create mock patient and events objects for testing
                $mockPatient = (object) ['name' => 'Test Patient'];
                $mockEvents = (object) ['created_at' => now()->format('Y-m-d H:i:s')];
                $notification = new $notificationClass($mockPatient, $mockEvents);
            } elseif ($notificationClass === 'App\\Notifications\\ReachingSpecificPoints') {
                // Create mock score for testing
                $mockScore = 100;
                $notification = new $notificationClass($mockScore);
            } elseif ($notificationClass === 'App\\Notifications\\ContactRequestNotification') {
                // Create mock recipient emails and message for testing
                $mockRecipientEmails = ['test@example.com'];
                $mockMessage = 'Test contact request message';
                $notification = new $notificationClass($mockRecipientEmails, $mockMessage);
            } else {
                $notification = new $notificationClass();
            }

            if (method_exists($notification, 'toBrevoApi')) {
                $data = $notification->toBrevoApi($testUser);

                $brevoService = new BrevoApiService();

                $result = $brevoService->sendEmail(
                    $data['to'],
                    $data['subject'],
                    $data['htmlContent'],
                    $data['textContent'] ?? null,
                    $data['from'] ?? null
                );

                return [
                    'success' => $result['success'],
                    'message_id' => $result['message_id'] ?? null,
                    'error' => $result['error'] ?? null,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Notification does not support Brevo API',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send notification via Laravel Notification
     */
    private function sendNotificationViaLaravel(string $notificationClass, User $testUser): array
    {
        try {
            // Handle notifications that require constructor parameters
            if ($notificationClass === 'App\\Notifications\\ReminderNotification') {
                // Create mock patient and events objects for testing
                $mockPatient = (object) ['name' => 'Test Patient'];
                $mockEvents = (object) ['created_at' => now()->format('Y-m-d H:i:s')];
                $notification = new $notificationClass($mockPatient, $mockEvents);
            } elseif ($notificationClass === 'App\\Notifications\\ReachingSpecificPoints') {
                // Create mock score for testing
                $mockScore = 100;
                $notification = new $notificationClass($mockScore);
            } elseif ($notificationClass === 'App\\Notifications\\ContactRequestNotification') {
                // Create mock recipient emails and message for testing
                $mockRecipientEmails = ['test@example.com'];
                $mockMessage = 'Test contact request message';
                $notification = new $notificationClass($mockRecipientEmails, $mockMessage);
            } else {
                $notification = new $notificationClass();
            }

            Notification::send($testUser, $notification);

            return [
                'success' => true,
                'message_id' => 'Laravel-Notification-'.now()->timestamp,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Display test results
     */
    private function displayResults(array $results): void
    {
        $this->info('');
        $this->info('📊 Test Results Summary');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info("✅ Successful: {$results['success']}");
        $this->info("❌ Failed: {$results['failed']}");
        $this->info('📧 Total Tested: '.($results['success'] + $results['failed']));

        if (! empty($results['details'])) {
            $this->info('');
            $this->info('📋 Detailed Results:');
            $this->info('═══════════════════════════════════════════════════════════════');

            foreach ($results['details'] as $detail) {
                $this->info("{$detail['status']} {$detail['type']}: {$detail['name']}");
                if (isset($detail['message_id'])) {
                    $this->info("   📧 Message ID: {$detail['message_id']}");
                }
                if (isset($detail['error'])) {
                    $this->info("   ❌ Error: {$detail['error']}");
                }
                $this->info("   🔧 Method: {$detail['method']}");
                $this->info('');
            }
        }

        $this->info('═══════════════════════════════════════════════════════════════');

        if ($results['failed'] === 0) {
            $this->info('🎉 All mail templates tested successfully!');
        } else {
            $this->warn("⚠️  {$results['failed']} mail template(s) failed. Check the errors above.");
        }
    }

    /**
     * Validate mailable without sending
     */
    private function validateMailable(string $mailableClass, User $testUser): array
    {
        try {
            // Handle VerifyEmail class which requires a verification URL
            if ($mailableClass === VerifyEmail::class) {
                $mailable = new VerifyEmail('https://test.egyakin.com/verify?token=test123');
            } else {
                $mailable = new $mailableClass();
            }

            // Validate that we can get the envelope and content
            $envelope = $mailable->envelope();
            $content = $mailable->content();

            // Try to render the view to check for errors
            $htmlContent = view($content->view, array_merge($content->with ?? [], ['data' => $mailable->reportData ?? []]))->render();

            return [
                'success' => true,
                'message_id' => 'DRY-RUN-MAILABLE-'.now()->timestamp,
                'validation' => 'Template structure and content validated successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate notification without sending
     */
    private function validateNotification(string $notificationClass, User $testUser): array
    {
        try {
            // Handle notifications that require constructor parameters
            if ($notificationClass === 'App\\Notifications\\ReminderNotification') {
                // Create mock patient and events objects for testing
                $mockPatient = (object) ['name' => 'Test Patient'];
                $mockEvents = (object) ['created_at' => now()->format('Y-m-d H:i:s')];
                $notification = new $notificationClass($mockPatient, $mockEvents);
            } elseif ($notificationClass === 'App\\Notifications\\ReachingSpecificPoints') {
                // Create mock score for testing
                $mockScore = 100;
                $notification = new $notificationClass($mockScore);
            } elseif ($notificationClass === 'App\\Notifications\\ContactRequestNotification') {
                // Create mock recipient emails and message for testing
                $mockRecipientEmails = ['test@example.com'];
                $mockMessage = 'Test contact request message';
                $notification = new $notificationClass($mockRecipientEmails, $mockMessage);
            } else {
                $notification = new $notificationClass();
            }

            // Validate that we can get the Brevo API data
            if (method_exists($notification, 'toBrevoApi')) {
                $data = $notification->toBrevoApi($testUser);

                // Validate required fields
                if (! isset($data['to']) || ! isset($data['subject']) || ! isset($data['htmlContent'])) {
                    throw new \Exception('Missing required fields in toBrevoApi response');
                }

                return [
                    'success' => true,
                    'message_id' => 'DRY-RUN-NOTIFICATION-'.now()->timestamp,
                    'validation' => 'Notification structure and content validated successfully',
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Notification does not support Brevo API',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
