<?php

namespace Tests\Feature\Api\V2\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Authentication endpoints
 *
 * Tests the following endpoints:
 * - POST /api/v2/register
 * - POST /api/v2/login
 * - POST /api/v2/logout
 *
 * @group auth
 * @group api
 * @group v2
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create default roles
        Role::create(['name' => 'user']);
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'Admin']);
    }

    // ==================== REGISTRATION TESTS ====================

    /** @test */
    public function test_user_can_register_with_valid_data()
    {
        $userData = [
            'name' => fake()->firstName(),
            'lname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'age' => fake()->numberBetween(25, 65),
            'specialty' => fake()->word(),
            'workingplace' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'job' => fake()->jobTitle(),
            'highestdegree' => fake()->words(2, true),
        ];

        $response = $this->postJson('/api/v2/register', $userData);

        $response->assertStatus(201) // 201 Created is the correct status for user registration
            ->assertJson(['value' => true])
            ->assertJsonStructure([
                'value',
                'message',
                'token',
                'data' => ['id', 'name', 'lname', 'email'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => strtolower($userData['email']), // Email is stored in lowercase
            'name' => $userData['name'],
        ]);
    }

    /** @test */
    public function test_registration_validates_required_fields()
    {
        $response = $this->postJson('/api/v2/register', []);

        $this->assertValidationError($response, [
            'name',
            'email',
            'password',
        ]);
    }

    /** @test */
    public function test_registration_prevents_duplicate_email()
    {
        $existingUser = User::factory()->create();

        $userData = [
            'name' => fake()->firstName(),
            'lname' => fake()->lastName(),
            'email' => $existingUser->email, // Duplicate email
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'age' => 30,
            'specialty' => 'Cardiology',
            'workingplace' => 'Hospital',
            'phone' => '1234567890',
            'job' => 'Doctor',
            'highestdegree' => 'MD',
        ];

        $response = $this->postJson('/api/v2/register', $userData);

        $this->assertValidationError($response, ['email']);
    }

    /** @test */
    public function test_registration_validates_password_minimum_length()
    {
        $userData = [
            'name' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'short', // Less than 8 characters
        ];

        $response = $this->postJson('/api/v2/register', $userData);

        $this->assertValidationError($response, ['password']);
    }

    /** @test */
    public function test_registration_assigns_default_user_type_and_role()
    {
        $userData = [
            'name' => fake()->firstName(),
            'lname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'age' => 30,
            'specialty' => 'Cardiology',
            'workingplace' => 'Hospital',
            'phone' => '1234567890',
            'job' => 'Doctor',
            'highestdegree' => 'MD',
        ];

        $response = $this->postJson('/api/v2/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', $userData['email'])->first();
        $this->assertEquals('normal', $user->user_type);
        $this->assertTrue($user->hasRole('user'));
    }

    /** @test */
    public function test_registration_assigns_doctor_role_for_medical_statistics_user_type()
    {
        $userData = [
            'name' => fake()->firstName(),
            'lname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'medical_statistics',
            'age' => 30,
            'specialty' => 'Cardiology',
            'workingplace' => 'Hospital',
            'phone' => '1234567890',
            'job' => 'Doctor',
            'highestdegree' => 'MD',
        ];

        $response = $this->postJson('/api/v2/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', $userData['email'])->first();
        $this->assertEquals('medical_statistics', $user->user_type);
        $this->assertTrue($user->hasRole('doctor'));
    }

    /** @test */
    public function test_registration_trims_whitespace_from_email()
    {
        $userData = [
            'name' => fake()->firstName(),
            'lname' => fake()->lastName(),
            'email' => '  test@example.com  ',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'age' => 30,
            'specialty' => 'Cardiology',
            'workingplace' => 'Hospital',
            'phone' => '1234567890',
            'job' => 'Doctor',
            'highestdegree' => 'MD',
        ];

        $response = $this->postJson('/api/v2/register', $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com', // Trimmed
        ]);
    }

    // ==================== LOGIN TESTS ====================

    /** @test */
    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v2/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['value' => true])
            ->assertJsonStructure([
                'value',
                'message',
                'token',
                'data' => ['id', 'name', 'email'],
            ]);
    }

    /** @test */
    public function test_login_rejects_invalid_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v2/login', [
            'email' => $user->email,
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function test_login_validates_required_fields()
    {
        $response = $this->postJson('/api/v2/login', []);

        $this->assertValidationError($response, ['email', 'password']);
    }

    /** @test */
    public function test_login_returns_token_on_success()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v2/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $token = $response->json('token');
        $this->assertNotEmpty($token);
    }

    /** @test */
    public function test_login_is_case_insensitive_for_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v2/login', [
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'password123',
        ]);

        // This might fail if the backend doesn't handle case-insensitive emails
        // If it does fail, it's a bug that should be fixed
        $response->assertStatus(200);
    }

    /** @test */
    public function test_login_stores_fcm_token_when_provided()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $fcmToken = 'fake_fcm_token_' . fake()->uuid();

        $response = $this->postJson('/api/v2/login', [
            'email' => $user->email,
            'password' => 'password123',
            'fcmToken' => $fcmToken,
            'deviceId' => 'device123',
        ]);

        $response->assertStatus(200);

        // Verify FCM token is stored (implementation-specific)
        $this->assertDatabaseHas('fcm_tokens', [
            'doctor_id' => $user->id,
            'token' => $fcmToken,
        ]);
    }

    // ==================== LOGOUT TESTS ====================

    /** @test */
    public function test_authenticated_user_can_logout()
    {
        $user = $this->authenticatedUser();

        $response = $this->postJson('/api/v2/logout');

        $response->assertStatus(200)
            ->assertJson(['value' => true]);
    }

    /** @test */
    public function test_logout_requires_authentication()
    {
        $response = $this->postJson('/api/v2/logout');

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_logout_invalidates_token()
    {
        $user = User::factory()->create();

        // Create a token for the user
        $tokenModel = $user->createToken('auth_token');
        $token = $tokenModel->plainTextToken;

        // Verify we have one token
        $this->assertEquals(1, $user->tokens()->count());

        // Perform logout with token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v2/logout');

        $response->assertStatus(200);

        // Verify all tokens are deleted after logout
        $user->refresh();
        $this->assertEquals(0, $user->tokens()->count());

        // Verify the token no longer exists in the database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenModel->accessToken->id,
        ]);
    }
}
