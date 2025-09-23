<?php

namespace Tests\Feature\Modules\Auth;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => null, // Unverified user
        ]);
    }

    /** @test */
    public function it_can_send_verification_email()
    {
        $response = $this->postJson('/api/v1/email/verification-notification', [
            'email' => $this->user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'Verification email sent successfully',
            ]);
    }

    /** @test */
    public function it_validates_email_for_verification_request()
    {
        $response = $this->postJson('/api/v1/email/verification-notification', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_handles_non_existent_email_for_verification()
    {
        $response = $this->postJson('/api/v1/email/verification-notification', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'value' => false,
                'message' => 'User not found',
            ]);
    }

    /** @test */
    public function it_prevents_verification_email_for_already_verified_users()
    {
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/email/verification-notification', [
            'email' => $verifiedUser->email,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'value' => false,
                'message' => 'Email already verified',
            ]);
    }

    /** @test */
    public function it_can_verify_email_with_valid_token()
    {
        // Simulate receiving verification parameters
        $verificationData = [
            'id' => $this->user->id,
            'hash' => sha1($this->user->email),
            'expires' => now()->addMinutes(60)->timestamp,
            'signature' => 'valid_signature',
        ];

        $response = $this->postJson('/api/v1/email/verify', $verificationData);

        // Note: This test might need adjustment based on actual implementation
        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_send_otp_for_verification()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/sendverificationmail', [
            'email' => $this->user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'OTP sent successfully',
            ]);

        // Verify OTP was created
        $this->assertDatabaseHas('otps', [
            'identifier' => $this->user->email,
            'valid' => true,
        ]);
    }

    /** @test */
    public function it_can_verify_otp()
    {
        Sanctum::actingAs($this->user);

        // Create an OTP
        $otp = Otp::create([
            'identifier' => $this->user->email,
            'token' => '123456',
            'validity' => 10,
            'valid' => true,
        ]);

        $response = $this->postJson('/api/v1/emailverification', [
            'email' => $this->user->email,
            'otp' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'Email verified successfully',
            ]);

        // Verify user is now verified
        $this->user->refresh();
        $this->assertNotNull($this->user->email_verified_at);

        // Verify OTP was invalidated
        $otp->refresh();
        $this->assertFalse($otp->valid);
    }

    /** @test */
    public function it_rejects_invalid_otp()
    {
        Sanctum::actingAs($this->user);

        // Create an OTP
        Otp::create([
            'identifier' => $this->user->email,
            'token' => '123456',
            'validity' => 10,
            'valid' => true,
        ]);

        $response = $this->postJson('/api/v1/emailverification', [
            'email' => $this->user->email,
            'otp' => '654321', // Wrong OTP
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'value' => false,
                'message' => 'Invalid OTP',
            ]);

        // Verify user is still unverified
        $this->user->refresh();
        $this->assertNull($this->user->email_verified_at);
    }

    /** @test */
    public function it_rejects_expired_otp()
    {
        Sanctum::actingAs($this->user);

        // Create an expired OTP
        $otp = Otp::create([
            'identifier' => $this->user->email,
            'token' => '123456',
            'validity' => 10,
            'valid' => true,
            'created_at' => now()->subMinutes(15), // Expired
        ]);

        $response = $this->postJson('/api/v1/emailverification', [
            'email' => $this->user->email,
            'otp' => '123456',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'value' => false,
                'message' => 'OTP has expired',
            ]);
    }

    /** @test */
    public function it_can_resend_otp()
    {
        Sanctum::actingAs($this->user);

        // Send initial OTP
        $this->postJson('/api/v1/sendverificationmail', [
            'email' => $this->user->email,
        ]);

        // Resend OTP
        $response = $this->postJson('/api/v1/resendemailverification', [
            'email' => $this->user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'OTP resent successfully',
            ]);
    }

    /** @test */
    public function it_validates_otp_verification_data()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/emailverification', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'otp']);
    }

    /** @test */
    public function it_validates_otp_send_data()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/sendverificationmail', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_prevents_otp_verification_for_already_verified_users()
    {
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($verifiedUser);

        $response = $this->postJson('/api/v1/emailverification', [
            'email' => $verifiedUser->email,
            'otp' => '123456',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'value' => false,
                'message' => 'Email already verified',
            ]);
    }

    /** @test */
    public function it_limits_otp_attempts()
    {
        Sanctum::actingAs($this->user);

        // Create an OTP
        Otp::create([
            'identifier' => $this->user->email,
            'token' => '123456',
            'validity' => 10,
            'valid' => true,
        ]);

        // Make multiple failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/emailverification', [
                'email' => $this->user->email,
                'otp' => '000000', // Wrong OTP
            ]);
        }

        // Next attempt should be rate limited
        $response = $this->postJson('/api/v1/emailverification', [
            'email' => $this->user->email,
            'otp' => '000000',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'value' => false,
                'message' => 'Too many attempts. Please try again later.',
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_otp_endpoints()
    {
        $endpoints = [
            ['method' => 'post', 'uri' => '/api/v1/sendverificationmail'],
            ['method' => 'post', 'uri' => '/api/v1/emailverification'],
            ['method' => 'post', 'uri' => '/api/v1/resendemailverification'],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->{$endpoint['method'].'Json'}($endpoint['uri']);

            $response->assertStatus(401);
        }
    }

    /** @test */
    public function it_invalidates_old_otps_when_sending_new_one()
    {
        Sanctum::actingAs($this->user);

        // Send first OTP
        $this->postJson('/api/v1/sendverificationmail', [
            'email' => $this->user->email,
        ]);

        $firstOtpCount = Otp::where('identifier', $this->user->email)
            ->where('valid', true)
            ->count();

        // Send second OTP
        $this->postJson('/api/v1/sendverificationmail', [
            'email' => $this->user->email,
        ]);

        $validOtpCount = Otp::where('identifier', $this->user->email)
            ->where('valid', true)
            ->count();

        // Should still have only one valid OTP
        $this->assertEquals(1, $validOtpCount);
    }
}
