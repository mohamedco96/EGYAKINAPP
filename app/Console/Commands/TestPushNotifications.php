<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Notifications\Services\FcmTokenService;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestPushNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:push-notifications 
                            {--user-id= : Test with specific user ID}
                            {--token= : Test with specific FCM token}
                            {--all : Send test notification to all users}
                            {--admins : Send test notification to admin users only}
                            {--validate : Only validate tokens without sending}';

    /**
     * The console command description.
     */
    protected $description = 'Test push notification system with various scenarios';

    protected $notificationService;

    protected $fcmTokenService;

    public function __construct(NotificationService $notificationService, FcmTokenService $fcmTokenService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
        $this->fcmTokenService = $fcmTokenService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Push Notification Tests...');
        $this->line('');

        try {
            // Display current FCM token statistics
            $this->displayTokenStats();
            $this->line('');

            // Run validation if requested
            if ($this->option('validate')) {
                return $this->validateTokensOnly();
            }

            // Run specific test scenarios
            if ($this->option('user-id')) {
                return $this->testSpecificUser();
            }

            if ($this->option('token')) {
                return $this->testSpecificToken();
            }

            if ($this->option('all')) {
                return $this->testAllUsers();
            }

            if ($this->option('admins')) {
                return $this->testAdminUsers();
            }

            // Default: Interactive menu
            return $this->showInteractiveMenu();

        } catch (\Exception $e) {
            $this->error('❌ Test failed: '.$e->getMessage());
            Log::error('Push notification test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Display FCM token statistics
     */
    protected function displayTokenStats(): void
    {
        $totalTokens = FcmToken::count();
        $totalUsers = FcmToken::distinct('doctor_id')->count();
        $tokensPerUser = $totalUsers > 0 ? round($totalTokens / $totalUsers, 2) : 0;
        $oldTokens = FcmToken::where('created_at', '<', now()->subMonths(1))->count();

        $this->info('📊 FCM Token Statistics:');
        $this->line("   Total Tokens: {$totalTokens}");
        $this->line("   Users with Tokens: {$totalUsers}");
        $this->line("   Average Tokens per User: {$tokensPerUser}");
        $this->line("   Tokens older than 1 month: {$oldTokens}");
    }

    /**
     * Validate tokens without sending notifications
     */
    protected function validateTokensOnly(): int
    {
        $this->info('🔍 Validating FCM Tokens...');

        $tokens = FcmToken::all();
        $validTokens = 0;
        $invalidTokens = 0;

        foreach ($tokens as $tokenRecord) {
            if ($this->isValidTokenFormat($tokenRecord->token)) {
                $validTokens++;
                $this->line("✅ Valid: User {$tokenRecord->doctor_id} - ".substr($tokenRecord->token, 0, 20).'...');
            } else {
                $invalidTokens++;
                $this->line("❌ Invalid: User {$tokenRecord->doctor_id} - ".substr($tokenRecord->token, 0, 20).'...');
            }
        }

        $this->info("✅ Validation Complete: {$validTokens} valid, {$invalidTokens} invalid tokens");

        return self::SUCCESS;
    }

    /**
     * Test notification for specific user
     */
    protected function testSpecificUser(): int
    {
        $userId = $this->option('user-id');
        $user = User::find($userId);

        if (! $user) {
            $this->error("❌ User with ID {$userId} not found");

            return self::FAILURE;
        }

        $tokens = FcmToken::where('doctor_id', $userId)->pluck('token')->toArray();

        if (empty($tokens)) {
            $this->error("❌ No FCM tokens found for user {$userId}");

            return self::FAILURE;
        }

        $this->info("🎯 Testing push notification for user: {$user->name} (ID: {$userId})");
        $this->line('   Found {'.count($tokens).'} token(s)');

        return $this->sendTestNotification(
            "Test Notification for {$user->name} 🧪",
            'This is a test notification sent at '.now()->format('Y-m-d H:i:s'),
            $tokens
        );
    }

    /**
     * Test notification for specific token
     */
    protected function testSpecificToken(): int
    {
        $token = $this->option('token');

        if (! $this->isValidTokenFormat($token)) {
            $this->error('❌ Invalid FCM token format');

            return self::FAILURE;
        }

        $tokenRecord = FcmToken::where('token', $token)->first();
        $userInfo = $tokenRecord ? "User ID: {$tokenRecord->doctor_id}" : 'Unknown user';

        $this->info('🎯 Testing push notification for specific token');
        $this->line('   Token: '.substr($token, 0, 30).'...');
        $this->line("   {$userInfo}");

        return $this->sendTestNotification(
            'Token Test Notification 🔧',
            'This is a test notification for your specific FCM token',
            [$token]
        );
    }

    /**
     * Test notification for all users
     */
    protected function testAllUsers(): int
    {
        $tokens = FcmToken::pluck('token')->toArray();

        if (empty($tokens)) {
            $this->error('❌ No FCM tokens found in the system');

            return self::FAILURE;
        }

        if (! $this->confirm('Send test notification to ALL {'.count($tokens).'} tokens?')) {
            $this->info('Test cancelled by user');

            return self::SUCCESS;
        }

        $this->info('🌍 Testing push notification for ALL users');
        $this->line('   Sending to {'.count($tokens).'} token(s)');

        return $this->sendTestNotification(
            'System-wide Test Notification 📢',
            'This is a test notification sent to all users at '.now()->format('Y-m-d H:i:s'),
            $tokens
        );
    }

    /**
     * Test notification for admin users only
     */
    protected function testAdminUsers(): int
    {
        $adminUsers = User::role('Admin')->pluck('id');

        if ($adminUsers->isEmpty()) {
            $this->error('❌ No admin users found');

            return self::FAILURE;
        }

        $tokens = FcmToken::whereIn('doctor_id', $adminUsers)->pluck('token')->toArray();

        if (empty($tokens)) {
            $this->error('❌ No FCM tokens found for admin users');

            return self::FAILURE;
        }

        $this->info('👑 Testing push notification for ADMIN users only');
        $this->line('   Found {'.count($adminUsers).'} admin user(s)');
        $this->line('   Sending to {'.count($tokens).'} token(s)');

        return $this->sendTestNotification(
            'Admin Test Notification 👑',
            'This is a test notification sent to admin users only',
            $tokens
        );
    }

    /**
     * Show interactive menu
     */
    protected function showInteractiveMenu(): int
    {
        $this->info('🎮 Interactive Push Notification Test Menu');
        $this->line('');

        $choice = $this->choice(
            'What would you like to test?',
            [
                'validate' => 'Validate all FCM tokens (no sending)',
                'single_user' => 'Test notification for a specific user',
                'single_token' => 'Test notification for a specific token',
                'all_users' => 'Test notification for ALL users',
                'admin_users' => 'Test notification for admin users only',
                'sample_notifications' => 'Send sample notifications (different types)',
                'cleanup' => 'Clean up invalid tokens',
                'exit' => 'Exit',
            ]
        );

        switch ($choice) {
            case 'validate':
                return $this->validateTokensOnly();

            case 'single_user':
                $userId = $this->ask('Enter user ID to test');
                $this->input->setOption('user-id', $userId);

                return $this->testSpecificUser();

            case 'single_token':
                $token = $this->ask('Enter FCM token to test');
                $this->input->setOption('token', $token);

                return $this->testSpecificToken();

            case 'all_users':
                return $this->testAllUsers();

            case 'admin_users':
                return $this->testAdminUsers();

            case 'sample_notifications':
                return $this->sendSampleNotifications();

            case 'cleanup':
                return $this->cleanupTokens();

            case 'exit':
                $this->info('👋 Goodbye!');

                return self::SUCCESS;
        }

        return self::SUCCESS;
    }

    /**
     * Send different types of sample notifications
     */
    protected function sendSampleNotifications(): int
    {
        $tokens = FcmToken::limit(5)->pluck('token')->toArray(); // Limit to 5 for testing

        if (empty($tokens)) {
            $this->error('❌ No FCM tokens available for testing');

            return self::FAILURE;
        }

        $this->info('📱 Sending various sample notifications...');

        $notifications = [
            ['title' => 'New Patient Created 🏥', 'body' => 'Dr. Test created a new patient: John Doe'],
            ['title' => 'Outcome Submitted ✅', 'body' => 'Dr. Test submitted outcome for patient: Jane Smith'],
            ['title' => 'New Group Post 👥', 'body' => 'Dr. Test posted in your group'],
            ['title' => 'Comment was liked 👍', 'body' => 'Dr. Test liked your comment'],
            ['title' => 'Group Invitation Accepted 🎉', 'body' => 'Dr. Test accepted your group invitation'],
        ];

        foreach ($notifications as $index => $notification) {
            $this->line("   Sending: {$notification['title']}");

            $result = $this->sendTestNotification(
                $notification['title'],
                $notification['body'],
                $tokens
            );

            if ($result !== self::SUCCESS) {
                return $result;
            }

            // Small delay between notifications
            sleep(1);
        }

        $this->info('✅ All sample notifications sent successfully!');

        return self::SUCCESS;
    }

    /**
     * Clean up invalid tokens
     */
    protected function cleanupTokens(): int
    {
        $this->info('🧹 Cleaning up invalid FCM tokens...');

        $deletedCount = $this->fcmTokenService->cleanupInvalidTokens();

        $this->info("✅ Cleanup complete: {$deletedCount} tokens removed");

        return self::SUCCESS;
    }

    /**
     * Send a test notification
     */
    protected function sendTestNotification(string $title, string $body, array $tokens): int
    {
        try {
            $this->line('   📤 Sending notification...');

            $result = $this->notificationService->sendPushNotification($title, $body, $tokens);

            if ($result['success']) {
                $this->info('   ✅ Notification sent successfully!');
                $this->line("   📊 Sent to {$result['tokens_count']} token(s)");

                Log::info('Test push notification sent', [
                    'title' => $title,
                    'tokens_count' => $result['tokens_count'],
                    'test_mode' => true,
                ]);

                return self::SUCCESS;
            } else {
                $this->error("   ❌ Notification failed: {$result['status']}");

                return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("   ❌ Exception: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Validate FCM token format
     */
    protected function isValidTokenFormat(string $token): bool
    {
        return is_string($token) &&
               strlen($token) >= 152 &&
               preg_match('/^[a-zA-Z0-9:_-]+$/', $token);
    }
}
