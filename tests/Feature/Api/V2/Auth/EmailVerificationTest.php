<?php

namespace Tests\Feature\Api\V2\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Email Verification endpoints
 *
 * Tests the following endpoints:
 * - POST /api/v2/sendverificationmail
 * - POST /api/v2/emailverification
 *
 * @group auth
 * @group email-verification
 * @group api
 * @group v2
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    // ==================== SEND VERIFICATION EMAIL TESTS ====================

    /** @test */
    public function test_send_verification_mail_creates_otp()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/v2/sendverificationmail');

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        // Verify OTP was created
        $this->assertDatabaseHas('otps', [
            'email' => $user->email,
        ]);
    }

    /** @test */
    public function test_send_verification_mail_requires_authentication()
    {
        $response = $this->postJson('/api/v2/sendverificationmail');

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_send_verification_mail_for_already_verified_user()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/v2/sendverificationmail');

        // Should still succeed (idempotent operation)
        $response->assertStatus(200);
    }

    // ==================== EMAIL VERIFICATION TESTS ====================

    /** @test */
    public function test_email_verification_accepts_valid_otp()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user);

        // Send verification email
        $this->postJson('/api/v2/sendverificationmail');

        // Get the OTP from database
        $otp = \DB::table('otps')->where('email', $user->email)->first();

        // Verify email with OTP
        $response = $this->postJson('/api/v2/emailverification', [
            'otp' => $otp->otp,
        ]);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        // Verify email was marked as verified
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test */
    public function test_email_verification_rejects_invalid_otp()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user);

        // Send verification email
        $this->postJson('/api/v2/sendverificationmail');

        // Try to verify with invalid OTP
        $response = $this->postJson('/api/v2/emailverification', [
            'otp' => '000000', // Invalid OTP
        ]);

        $response->assertStatus(400);

        // Verify email was NOT marked as verified
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /** @test */
    public function test_email_verification_requires_authentication()
    {
        $response = $this->postJson('/api/v2/emailverification', [
            'otp' => '123456',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_email_verification_requires_otp_field()
    {
        $user = $this->authenticatedUser();

        $response = $this->postJson('/api/v2/emailverification', []);

        $this->assertValidationError($response, ['otp']);
    }

    /** @test */
    public function test_otp_expires_after_time_limit()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user);

        // Send verification email
        $this->postJson('/api/v2/sendverificationmail');

        // Get the OTP and manually expire it
        $otp = \DB::table('otps')->where('email', $user->email)->first();
        \DB::table('otps')
            ->where('id', $otp->id)
            ->update(['created_at' => now()->subHours(2)]); // Expired

        // Try to verify with expired OTP
        $response = $this->postJson('/api/v2/emailverification', [
            'otp' => $otp->otp,
        ]);

        // Should fail due to expiration
        $response->assertStatus(400);
    }

    /** @test */
    public function test_resend_otp_generates_new_code()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user);

        // Send first OTP
        $this->postJson('/api/v2/sendverificationmail');
        $firstOtp = \DB::table('otps')->where('email', $user->email)->first();

        // Wait a moment to ensure timestamps differ
        sleep(1);

        // Resend OTP
        $this->postJson('/api/v2/sendverificationmail');
        $secondOtp = \DB::table('otps')
            ->where('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->first();

        // New OTP should be created
        $this->assertNotEquals($firstOtp->id, $secondOtp->id);
    }

    /** @test */
    public function test_verification_marks_email_as_verified()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user);

        // Send verification email
        $this->postJson('/api/v2/sendverificationmail');

        // Get OTP
        $otp = \DB::table('otps')->where('email', $user->email)->first();

        // Verify email
        $this->postJson('/api/v2/emailverification', [
            'otp' => $otp->otp,
        ]);

        // Check that email_verified_at is now set
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasVerifiedEmail());
    }
}
