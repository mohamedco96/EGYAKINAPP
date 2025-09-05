<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->user = User::factory()->create([
            'name' => 'Test Doctor',
            'email' => 'test@example.com',
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        // Assign admin role
        $this->adminUser->assignRole('Admin');
    }

    /** @test */
    public function it_can_store_fcm_token_via_api()
    {
        Sanctum::actingAs($this->user);

        $token = $this->generateValidFcmToken();
        $deviceId = $this->generateValidDeviceId();

        $response = $this->postJson('/api/storeFCM', [
            'token' => $token,
            'deviceId' => $deviceId,
            'deviceType' => 'ios',
            'appVersion' => '1.0.0',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'value' => true,
                'message' => 'FCM token stored successfully',
            ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'doctor_id' => $this->user->id,
            'token' => $token,
            'device_id' => $deviceId,
        ]);
    }

    /** @test */
    public function it_rejects_invalid_fcm_token_format()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/storeFCM', [
            'token' => 'invalid-token-too-short',
        ]);

        $response->assertStatus(200) // Returns 200 but with error message
            ->assertJson([
                'value' => false,
                'message' => 'Invalid FCM token format.',
            ]);
    }

    /** @test */
    public function it_can_send_notification_to_all_users()
    {
        // Create FCM tokens for users
        FcmToken::create([
            'doctor_id' => $this->user->id,
            'token' => $this->generateValidFcmToken(),
            'device_id' => $this->generateValidDeviceId(),
        ]);

        FcmToken::create([
            'doctor_id' => $this->adminUser->id,
            'token' => $this->generateValidFcmToken(),
            'device_id' => $this->generateValidDeviceId(),
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/send-notification', [
            'title' => 'Test Notification',
            'body' => 'This is a test notification',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_send_predefined_notification_to_all()
    {
        // Create FCM tokens
        FcmToken::create([
            'doctor_id' => $this->user->id,
            'token' => $this->generateValidFcmToken(),
            'device_id' => $this->generateValidDeviceId(),
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/sendAllPushNotification');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_limits_tokens_per_user()
    {
        Sanctum::actingAs($this->user);

        // Create 6 tokens (should limit to 10 now due to multi-device support)
        for ($i = 0; $i < 12; $i++) {
            $this->postJson('/api/storeFCM', [
                'token' => $this->generateValidFcmToken(),
                'deviceId' => $this->generateValidDeviceId(),
            ]);
        }

        $tokenCount = FcmToken::where('doctor_id', $this->user->id)->count();
        $this->assertEquals(10, $tokenCount, 'Should limit to 10 tokens per user');
    }

    /** @test */
    public function it_updates_existing_device_token_instead_of_duplicating()
    {
        $deviceId = $this->generateValidDeviceId();

        // Create token for user with specific device
        Sanctum::actingAs($this->user);
        $response1 = $this->postJson('/api/storeFCM', [
            'token' => $this->generateValidFcmToken(),
            'deviceId' => $deviceId,
            'deviceType' => 'ios',
        ]);
        $response1->assertStatus(201);

        // Update token for same device (should update, not create new)
        $newToken = $this->generateValidFcmToken();
        $response2 = $this->postJson('/api/storeFCM', [
            'token' => $newToken,
            'deviceId' => $deviceId,
            'deviceType' => 'ios',
        ]);
        $response2->assertStatus(201);

        // Should have only one token record for this user+device combination
        $this->assertEquals(1, FcmToken::where('doctor_id', $this->user->id)
            ->where('device_id', $deviceId)->count());
        $this->assertDatabaseHas('fcm_tokens', [
            'token' => $newToken,
            'doctor_id' => $this->user->id,
            'device_id' => $deviceId,
        ]);
    }

    /** @test */
    public function it_validates_notification_request_data()
    {
        Sanctum::actingAs($this->adminUser);

        // Test missing title
        $response = $this->postJson('/api/send-notification', [
            'body' => 'Test body',
        ]);
        $response->assertStatus(422);

        // Test missing body
        $response = $this->postJson('/api/send-notification', [
            'title' => 'Test title',
        ]);
        $response->assertStatus(422);

        // Test title too long
        $response = $this->postJson('/api/send-notification', [
            'title' => str_repeat('a', 300),
            'body' => 'Test body',
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function it_handles_notification_service_properly()
    {
        $token = $this->generateValidFcmToken();

        FcmToken::create([
            'doctor_id' => $this->user->id,
            'token' => $token,
            'device_id' => $this->generateValidDeviceId(),
        ]);

        $notificationService = app(NotificationService::class);

        $result = $notificationService->sendPushNotification(
            'Test Title',
            'Test Body',
            [$token]
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['tokens_count']);
    }

    /** @test */
    public function it_handles_empty_token_array()
    {
        $notificationService = app(NotificationService::class);

        $result = $notificationService->sendPushNotification(
            'Test Title',
            'Test Body',
            []
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('No tokens found', $result['status']);
    }

    /** @test */
    public function it_filters_invalid_tokens()
    {
        $validToken = $this->generateValidFcmToken();
        $invalidToken = 'invalid-token';

        $notificationService = app(NotificationService::class);

        $result = $notificationService->sendPushNotification(
            'Test Title',
            'Test Body',
            [$validToken, $invalidToken]
        );

        // Should only process valid tokens
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['tokens_count']);
    }

    /**
     * Generate a valid FCM token format for testing
     */
    protected function generateValidFcmToken(): string
    {
        // Generate a realistic FCM token format (152+ characters)
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_:';
        $token = '';

        for ($i = 0; $i < 180; $i++) {
            $token .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $token;
    }

    /**
     * Generate a valid device ID format for testing
     */
    protected function generateValidDeviceId(): string
    {
        // Generate a realistic device ID format (UUID-like)
        return sprintf(
            '%08X-%04X-%04X-%04X-%12X',
            mt_rand(0, 0xFFFFFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFFFFFFFFFF)
        );
    }
}
