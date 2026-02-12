<?php

namespace Tests\Feature\Api\V2\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Password Management endpoints
 *
 * Tests the following endpoints:
 * - POST /api/v2/forgotpassword
 * - POST /api/v2/resetpasswordverification
 * - POST /api/v2/resetpassword
 * - POST /api/v2/changePassword
 *
 * @group auth
 * @group password
 * @group api
 * @group v2
 */
class PasswordManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    // ==================== FORGOT PASSWORD TESTS ====================

    /** @test */
    public function test_forgot_password_sends_reset_link()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v2/forgotpassword', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        // Verify OTP was created in database
        $this->assertDatabaseHas('otps', [
            'email' => $user->email,
        ]);
    }

    /** @test */
    public function test_forgot_password_validates_email()
    {
        $response = $this->postJson('/api/v2/forgotpassword', [
            'email' => '',
        ]);

        $this->assertValidationError($response, ['email']);
    }

    /** @test */
    public function test_forgot_password_handles_non_existent_email()
    {
        $response = $this->postJson('/api/v2/forgotpassword', [
            'email' => 'nonexistent@example.com',
        ]);

        // Should return success even for non-existent emails (security best practice)
        // to prevent email enumeration attacks
        $response->assertStatus(200);
    }

    /** @test */
    public function test_forgot_password_requires_valid_email_format()
    {
        $response = $this->postJson('/api/v2/forgotpassword', [
            'email' => 'invalid-email',
        ]);

        $this->assertValidationError($response, ['email']);
    }

    // ==================== RESET PASSWORD VERIFICATION TESTS ====================

    /** @test */
    public function test_reset_password_verification_validates_otp()
    {
        $user = User::factory()->create();

        // First, request password reset
        $this->postJson('/api/v2/forgotpassword', [
            'email' => $user->email,
        ]);

        // Get the OTP from database
        $otp = \DB::table('otps')->where('email', $user->email)->first();

        $response = $this->postJson('/api/v2/resetpasswordverification', [
            'email' => $user->email,
            'otp' => $otp->otp,
        ]);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);
    }

    /** @test */
    public function test_reset_password_verification_rejects_invalid_otp()
    {
        $user = User::factory()->create();

        // First, request password reset
        $this->postJson('/api/v2/forgotpassword', [
            'email' => $user->email,
        ]);

        $response = $this->postJson('/api/v2/resetpasswordverification', [
            'email' => $user->email,
            'otp' => '000000', // Invalid OTP
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function test_reset_password_verification_requires_fields()
    {
        $response = $this->postJson('/api/v2/resetpasswordverification', []);

        $this->assertValidationError($response, ['email', 'otp']);
    }

    // ==================== RESET PASSWORD TESTS ====================

    /** @test */
    public function test_reset_password_completes_successfully()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        // Request password reset
        $this->postJson('/api/v2/forgotpassword', [
            'email' => $user->email,
        ]);

        // Get OTP
        $otp = \DB::table('otps')->where('email', $user->email)->first();

        // Verify OTP
        $this->postJson('/api/v2/resetpasswordverification', [
            'email' => $user->email,
            'otp' => $otp->otp,
        ]);

        // Reset password
        $response = $this->postJson('/api/v2/resetpassword', [
            'email' => $user->email,
            'password' => 'new_password123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('new_password123', $user->password));
    }

    /** @test */
    public function test_reset_password_requires_all_fields()
    {
        $response = $this->postJson('/api/v2/resetpassword', []);

        $this->assertValidationError($response, ['email', 'password']);
    }

    /** @test */
    public function test_reset_password_validates_password_length()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v2/resetpassword', [
            'email' => $user->email,
            'password' => '123', // Too short
        ]);

        $this->assertValidationError($response, ['password']);
    }

    // ==================== CHANGE PASSWORD TESTS ====================

    /** @test */
    public function test_change_password_updates_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/v2/changePassword', [
            'current_password' => 'old_password',
            'new_password' => 'new_password123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('new_password123', $user->password));
    }

    /** @test */
    public function test_change_password_validates_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/v2/changePassword', [
            'current_password' => 'wrong_password',
            'new_password' => 'new_password123',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function test_change_password_requires_authentication()
    {
        $response = $this->postJson('/api/v2/changePassword', [
            'current_password' => 'old_password',
            'new_password' => 'new_password123',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_change_password_requires_all_fields()
    {
        $user = $this->authenticatedUser();

        $response = $this->postJson('/api/v2/changePassword', []);

        $this->assertValidationError($response, ['current_password', 'new_password']);
    }

    /** @test */
    public function test_change_password_enforces_minimum_length()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/v2/changePassword', [
            'current_password' => 'old_password',
            'new_password' => '123', // Too short
        ]);

        $this->assertValidationError($response, ['new_password']);
    }
}
