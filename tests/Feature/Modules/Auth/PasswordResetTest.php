<?php

namespace Tests\Feature\Modules\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword123'),
        ]);
    }

    /** @test */
    public function it_can_request_password_reset()
    {
        $response = $this->postJson('/api/v1/forgotpassword', [
            'email' => $this->user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'Password reset email sent successfully',
            ]);

        // Verify reset token was created
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $this->user->email,
        ]);
    }

    /** @test */
    public function it_validates_email_for_password_reset_request()
    {
        $response = $this->postJson('/api/v1/forgotpassword', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_handles_non_existent_email_for_password_reset()
    {
        $response = $this->postJson('/api/v1/forgotpassword', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'value' => false,
                'message' => 'User not found',
            ]);
    }

    /** @test */
    public function it_can_verify_password_reset_token()
    {
        // Create a password reset token
        $token = Password::createToken($this->user);

        $response = $this->postJson('/api/v1/resetpasswordverification', [
            'email' => $this->user->email,
            'token' => $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'Token is valid',
            ]);
    }

    /** @test */
    public function it_rejects_invalid_password_reset_token()
    {
        $response = $this->postJson('/api/v1/resetpasswordverification', [
            'email' => $this->user->email,
            'token' => 'invalid_token',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'value' => false,
                'message' => 'Invalid or expired token',
            ]);
    }

    /** @test */
    public function it_validates_password_reset_verification_data()
    {
        $response = $this->postJson('/api/v1/resetpasswordverification', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'token']);
    }

    /** @test */
    public function it_can_reset_password_with_valid_token()
    {
        // Create a password reset token
        $token = Password::createToken($this->user);

        $response = $this->postJson('/api/v1/resetpassword', [
            'email' => $this->user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'Password reset successfully',
            ]);

        // Verify password was changed
        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));

        // Verify token was consumed
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $this->user->email,
            'token' => hash('sha256', $token),
        ]);
    }

    /** @test */
    public function it_validates_password_reset_data()
    {
        $response = $this->postJson('/api/v1/resetpassword', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'email',
                'token',
                'password',
            ]);
    }

    /** @test */
    public function it_requires_password_confirmation_for_reset()
    {
        $token = Password::createToken($this->user);

        $response = $this->postJson('/api/v1/resetpassword', [
            'email' => $this->user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_rejects_password_reset_with_invalid_token()
    {
        $response = $this->postJson('/api/v1/resetpassword', [
            'email' => $this->user->email,
            'token' => 'invalid_token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'value' => false,
                'message' => 'Invalid or expired token',
            ]);
    }

    /** @test */
    public function it_rejects_password_reset_with_expired_token()
    {
        // Create token and manually expire it
        $token = Password::createToken($this->user);

        // Update the token's created_at to be older than expiry time
        DB::table('password_reset_tokens')
            ->where('email', $this->user->email)
            ->update(['created_at' => now()->subHours(2)]);

        $response = $this->postJson('/api/v1/resetpassword', [
            'email' => $this->user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'value' => false,
                'message' => 'Invalid or expired token',
            ]);
    }

    /** @test */
    public function it_enforces_password_strength_requirements()
    {
        $token = Password::createToken($this->user);

        $weakPasswords = [
            '123',           // Too short
            'password',      // Too common
            '12345678',      // No letters
            'abcdefgh',      // No numbers
        ];

        foreach ($weakPasswords as $weakPassword) {
            $response = $this->postJson('/api/v1/resetpassword', [
                'email' => $this->user->email,
                'token' => $token,
                'password' => $weakPassword,
                'password_confirmation' => $weakPassword,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        }
    }

    /** @test */
    public function it_can_handle_multiple_reset_requests()
    {
        // First request
        $response1 = $this->postJson('/api/v1/forgotpassword', [
            'email' => $this->user->email,
        ]);
        $response1->assertStatus(200);

        // Second request (should replace the first token)
        $response2 = $this->postJson('/api/v1/forgotpassword', [
            'email' => $this->user->email,
        ]);
        $response2->assertStatus(200);

        // Should only have one token in database
        $tokenCount = DB::table('password_reset_tokens')
            ->where('email', $this->user->email)
            ->count();

        $this->assertEquals(1, $tokenCount);
    }

    /** @test */
    public function it_prevents_password_reset_for_unverified_users()
    {
        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/forgotpassword', [
            'email' => $unverifiedUser->email,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'value' => false,
                'message' => 'Email not verified',
            ]);
    }
}
