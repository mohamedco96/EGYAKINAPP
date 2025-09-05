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

        $response = $this->postJson('/api/storeFCM', [
            'token' => $token,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'value' => true,
                'message' => 'FCM token stored successfully',
            ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'doctor_id' => $this->user->id,
            'token' => $token,
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
        ]);

        FcmToken::create([
            'doctor_id' => $this->adminUser->id,
            'token' => $this->generateValidFcmToken(),
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
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/sendAllPushNotification');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_limits_tokens_per_user()
    {
        Sanctum::actingAs($this->user);

        // Create 6 tokens (should limit to 5)
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/storeFCM', [
                'token' => $this->generateValidFcmToken(),
            ]);
        }

        $tokenCount = FcmToken::where('doctor_id', $this->user->id)->count();
        $this->assertEquals(5, $tokenCount, 'Should limit to 5 tokens per user');
    }

    /** @test */
    public function it_updates_existing_token_instead_of_duplicating()
    {
        $token = $this->generateValidFcmToken();

        // Create token for first user
        FcmToken::create([
            'doctor_id' => $this->user->id,
            'token' => $token,
        ]);

        // Try to store same token for different user
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/storeFCM', [
            'token' => $token,
        ]);

        $response->assertStatus(201);

        // Should have only one token record, updated to new user
        $this->assertEquals(1, FcmToken::where('token', $token)->count());
        $this->assertDatabaseHas('fcm_tokens', [
            'token' => $token,
            'doctor_id' => $this->adminUser->id,
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
}
