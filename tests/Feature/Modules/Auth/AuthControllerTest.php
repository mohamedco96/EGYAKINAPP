<?php

namespace Tests\Feature\Modules\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function it_can_register_a_new_user()
    {
        $userData = [
            'name' => $this->faker->firstName(),
            'lname' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'age' => $this->faker->numberBetween(25, 65),
            'specialty' => $this->faker->word(),
            'workingplace' => $this->faker->company(),
            'phone' => $this->faker->phoneNumber(),
            'job' => $this->faker->jobTitle(),
            'highestdegree' => $this->faker->words(2, true),
        ];

        $response = $this->postJson('/api/v1/register', $userData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'value',
                'message',
                'token',
                'data' => [
                    'id',
                    'name',
                    'lname',
                    'email',
                    'age',
                    'specialty',
                    'workingplace',
                    'phone',
                    'job',
                    'highestdegree',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'value' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'name' => $userData['name'],
            'lname' => $userData['lname'],
        ]);
    }

    /** @test */
    public function it_validates_registration_data()
    {
        $response = $this->postJson('/api/v1/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'lname',
                'email',
                'password',
                'age',
                'specialty',
                'workingplace',
                'phone',
                'job',
                'highestdegree',
            ]);
    }

    /** @test */
    public function it_prevents_duplicate_email_registration()
    {
        $userData = [
            'name' => $this->faker->firstName(),
            'lname' => $this->faker->lastName(),
            'email' => $this->user->email, // Use existing user's email
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'age' => $this->faker->numberBetween(25, 65),
            'specialty' => $this->faker->word(),
            'workingplace' => $this->faker->company(),
            'phone' => $this->faker->phoneNumber(),
            'job' => $this->faker->jobTitle(),
            'highestdegree' => $this->faker->words(2, true),
        ];

        $response = $this->postJson('/api/v1/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_can_login_with_valid_credentials()
    {
        $loginData = [
            'email' => $this->user->email,
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'value',
                'message',
                'token',
                'data' => [
                    'id',
                    'name',
                    'lname',
                    'email',
                ],
            ])
            ->assertJson([
                'value' => true,
            ]);
    }

    /** @test */
    public function it_rejects_login_with_invalid_credentials()
    {
        $loginData = [
            'email' => $this->user->email,
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/v1/login', $loginData);

        $response->assertStatus(401)
            ->assertJson([
                'value' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /** @test */
    public function it_validates_login_data()
    {
        $response = $this->postJson('/api/v1/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /** @test */
    public function it_can_logout_authenticated_user()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'Logged out successfully',
            ]);
    }

    /** @test */
    public function it_can_get_all_users()
    {
        Sanctum::actingAs($this->user);

        // Create additional users
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'value',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'lname',
                        'email',
                        'specialty',
                        'workingplace',
                    ],
                ],
            ])
            ->assertJson([
                'value' => true,
            ]);
    }

    /** @test */
    public function it_can_get_user_by_id()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/users/{$this->user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'value',
                'data' => [
                    'id',
                    'name',
                    'lname',
                    'email',
                    'age',
                    'specialty',
                    'workingplace',
                    'phone',
                    'job',
                    'highestdegree',
                ],
            ])
            ->assertJson([
                'value' => true,
                'data' => [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_user()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/users/99999');

        $response->assertStatus(404)
            ->assertJson([
                'value' => false,
                'message' => 'No user was found',
            ]);
    }

    /** @test */
    public function it_can_show_another_user_profile()
    {
        Sanctum::actingAs($this->user);

        $anotherUser = User::factory()->create();

        $response = $this->getJson("/api/v1/showAnotherProfile/{$anotherUser->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'value',
                'data' => [
                    'id',
                    'name',
                    'lname',
                    'specialty',
                    'workingplace',
                ],
            ])
            ->assertJson([
                'value' => true,
            ]);
    }

    /** @test */
    public function it_can_update_user_profile()
    {
        Sanctum::actingAs($this->user);

        $updateData = [
            'name' => 'Updated Name',
            'lname' => 'Updated LastName',
            'specialty' => 'Updated Specialty',
            'workingplace' => 'Updated Workplace',
            'phone' => '+1234567890',
            'job' => 'Updated Job',
            'highestdegree' => 'Updated Degree',
        ];

        $response = $this->putJson('/api/v1/users', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'Profile updated successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'lname' => 'Updated LastName',
            'specialty' => 'Updated Specialty',
        ]);
    }

    /** @test */
    public function it_can_change_password()
    {
        Sanctum::actingAs($this->user);

        $passwordData = [
            'current_password' => 'password123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ];

        $response = $this->postJson('/api/v1/changePassword', $passwordData);

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'Password changed successfully',
            ]);

        // Verify new password works
        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    /** @test */
    public function it_validates_password_change_data()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/changePassword', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'current_password',
                'new_password',
            ]);
    }

    /** @test */
    public function it_rejects_password_change_with_wrong_current_password()
    {
        Sanctum::actingAs($this->user);

        $passwordData = [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ];

        $response = $this->postJson('/api/v1/changePassword', $passwordData);

        $response->assertStatus(400)
            ->assertJson([
                'value' => false,
                'message' => 'Current password is incorrect',
            ]);
    }

    /** @test */
    public function it_can_upload_profile_image()
    {
        Sanctum::actingAs($this->user);
        Storage::fake('public');

        $file = UploadedFile::fake()->image('profile.jpg', 300, 300);

        $response = $this->postJson('/api/v1/upload-profile-image', [
            'profile_image' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'Profile image uploaded successfully',
            ]);

        // Verify file was stored
        Storage::disk('public')->assertExists('profile_images/'.$file->hashName());
    }

    /** @test */
    public function it_validates_profile_image_upload()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/upload-profile-image', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['profile_image']);
    }

    /** @test */
    public function it_can_upload_syndicate_card()
    {
        Sanctum::actingAs($this->user);
        Storage::fake('public');

        $file = UploadedFile::fake()->image('syndicate.jpg', 600, 400);

        $response = $this->postJson('/api/v1/uploadSyndicateCard', [
            'syndicate_card' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'Syndicate card uploaded successfully',
            ]);

        // Verify file was stored
        Storage::disk('public')->assertExists('syndicate_cards/'.$file->hashName());
    }

    /** @test */
    public function it_can_delete_user()
    {
        Sanctum::actingAs($this->user);

        $userToDelete = User::factory()->create();

        $response = $this->deleteJson("/api/v1/users/{$userToDelete->id}");

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'User deleted successfully',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    }

    /** @test */
    public function it_can_store_fcm_token()
    {
        Sanctum::actingAs($this->user);

        $fcmData = [
            'token' => 'sample_fcm_token_12345',
            'device_id' => 'device_123',
            'device_type' => 'android',
            'app_version' => '1.0.0',
        ];

        $response = $this->postJson('/api/v1/storeFCM', $fcmData);

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'FCM token stored successfully',
            ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'doctor_id' => $this->user->id,
            'token' => $fcmData['token'],
            'device_id' => $fcmData['device_id'],
        ]);
    }

    /** @test */
    public function it_requires_authentication_for_protected_endpoints()
    {
        $protectedEndpoints = [
            ['method' => 'get', 'uri' => '/api/v1/users'],
            ['method' => 'get', 'uri' => '/api/v1/users/1'],
            ['method' => 'put', 'uri' => '/api/v1/users'],
            ['method' => 'post', 'uri' => '/api/v1/logout'],
            ['method' => 'post', 'uri' => '/api/v1/changePassword'],
            ['method' => 'delete', 'uri' => '/api/v1/users/1'],
        ];

        foreach ($protectedEndpoints as $endpoint) {
            $response = $this->{$endpoint['method'].'Json'}($endpoint['uri']);

            $response->assertStatus(401);
        }
    }

    /** @test */
    public function it_can_get_doctor_patients()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/doctorProfileGetPatients/{$this->user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'value',
                'data',
            ]);
    }

    /** @test */
    public function it_can_get_doctor_score_history()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/doctorProfileGetScoreHistory/{$this->user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'value',
                'data',
            ]);
    }

    /** @test */
    public function it_can_update_user_by_id()
    {
        Sanctum::actingAs($this->user);

        $userToUpdate = User::factory()->create();

        $updateData = [
            'name' => 'Admin Updated Name',
            'specialty' => 'Admin Updated Specialty',
        ];

        $response = $this->putJson("/api/v1/users/{$userToUpdate->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'Admin Updated Name',
            'specialty' => 'Admin Updated Specialty',
        ]);
    }
}
