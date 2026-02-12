<?php

namespace Tests\Feature\Api\V2\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Profile Management endpoints
 *
 * Tests the following endpoints:
 * - PUT /api/v2/users
 * - POST /api/v2/upload-profile-image
 * - POST /api/v2/uploadSyndicateCard
 * - POST /api/v2/storeFCM
 *
 * @group auth
 * @group profile
 * @group api
 * @group v2
 */
class ProfileManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    // ==================== PROFILE UPDATE TESTS ====================

    /** @test */
    public function test_user_can_update_own_profile()
    {
        $user = $this->authenticatedUser();

        $updateData = [
            'name' => 'Updated Name',
            'lname' => 'Updated LastName',
            'email' => $user->email, // Keep same email
            'age' => 35,
            'specialty' => 'Updated Specialty',
            'workingplace' => 'Updated Hospital',
            'phone' => '9876543210',
            'job' => 'Updated Job',
            'highestdegree' => 'PhD',
            'registration_number' => 'REG12345',
        ];

        $response = $this->putJson('/api/v2/users', $updateData);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'lname' => 'Updated LastName',
            'age' => 35,
        ]);
    }

    /** @test */
    public function test_profile_update_requires_authentication()
    {
        $response = $this->putJson('/api/v2/users', [
            'name' => 'Test',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_profile_update_validates_required_fields()
    {
        $user = $this->authenticatedUser();

        $response = $this->putJson('/api/v2/users', []);

        $this->assertValidationError($response, ['name', 'lname', 'email']);
    }

    /** @test */
    public function test_profile_update_validates_email_format()
    {
        $user = $this->authenticatedUser();

        $response = $this->putJson('/api/v2/users', [
            'name' => 'Test',
            'lname' => 'User',
            'email' => 'invalid-email',
        ]);

        $this->assertValidationError($response, ['email']);
    }

    /** @test */
    public function test_profile_update_returns_updated_data()
    {
        $user = $this->authenticatedUser();

        $updateData = [
            'name' => 'New Name',
            'lname' => 'New LastName',
            'email' => $user->email,
            'age' => 40,
            'specialty' => 'Cardiology',
            'workingplace' => 'Main Hospital',
            'phone' => '1234567890',
            'job' => 'Senior Doctor',
            'highestdegree' => 'MD',
            'registration_number' => 'REG999',
        ];

        $response = $this->putJson('/api/v2/users', $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'value',
                'message',
                'data' => ['user' => ['id', 'name', 'lname', 'email']],
            ]);
    }

    /** @test */
    public function test_profile_update_handles_partial_data()
    {
        $user = $this->authenticatedUser([
            'name' => 'Original Name',
            'age' => 30,
        ]);

        // Update only name and email
        $response = $this->putJson('/api/v2/users', [
            'name' => 'Updated Name',
            'lname' => $user->lname,
            'email' => $user->email,
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals(30, $user->age); // Should remain unchanged
    }

    // ==================== PROFILE IMAGE UPLOAD TESTS ====================

    /** @test */
    public function test_upload_profile_image_successfully()
    {
        Storage::fake('public');
        $user = $this->authenticatedUser();

        $image = $this->createFakeImage('profile.jpg');

        $response = $this->postJson('/api/v2/upload-profile-image', [
            'image' => $image,
        ]);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        // Verify image was stored
        $user->refresh();
        $this->assertNotNull($user->avatar);
    }

    /** @test */
    public function test_upload_profile_image_requires_authentication()
    {
        Storage::fake('public');

        $image = $this->createFakeImage('profile.jpg');

        $response = $this->postJson('/api/v2/upload-profile-image', [
            'image' => $image,
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_upload_profile_image_validates_file_type()
    {
        Storage::fake('public');
        $user = $this->authenticatedUser();

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/v2/upload-profile-image', [
            'image' => $file,
        ]);

        $this->assertValidationError($response, ['image']);
    }

    /** @test */
    public function test_upload_profile_image_validates_file_size()
    {
        Storage::fake('public');
        $user = $this->authenticatedUser();

        // Create an image larger than allowed (e.g., 10MB)
        $largeImage = UploadedFile::fake()->image('large.jpg')->size(10240);

        $response = $this->postJson('/api/v2/upload-profile-image', [
            'image' => $largeImage,
        ]);

        // Should fail validation if size limit is enforced
        $this->assertValidationError($response, ['image']);
    }

    /** @test */
    public function test_profile_image_url_is_accessible()
    {
        Storage::fake('public');
        $user = $this->authenticatedUser();

        $image = $this->createFakeImage('profile.jpg');

        $this->postJson('/api/v2/upload-profile-image', [
            'image' => $image,
        ]);

        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->assertStringContainsString('storage', $user->avatar);
    }

    // ==================== SYNDICATE CARD UPLOAD TESTS ====================

    /** @test */
    public function test_upload_syndicate_card_successfully()
    {
        Storage::fake('public');
        $user = $this->authenticatedUser();

        $card = $this->createFakeImage('syndicate_card.jpg');

        $response = $this->postJson('/api/v2/uploadSyndicateCard', [
            'syndicate_card' => $card,
        ]);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        // Verify card was stored
        $user->refresh();
        $this->assertNotNull($user->syndicate_card);
    }

    /** @test */
    public function test_upload_syndicate_card_requires_authentication()
    {
        Storage::fake('public');

        $card = $this->createFakeImage('syndicate_card.jpg');

        $response = $this->postJson('/api/v2/uploadSyndicateCard', [
            'syndicate_card' => $card,
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_upload_syndicate_card_validates_file_type()
    {
        Storage::fake('public');
        $user = $this->authenticatedUser();

        $file = UploadedFile::fake()->create('document.txt', 100);

        $response = $this->postJson('/api/v2/uploadSyndicateCard', [
            'syndicate_card' => $file,
        ]);

        $this->assertValidationError($response, ['syndicate_card']);
    }

    /** @test */
    public function test_syndicate_card_url_is_accessible()
    {
        Storage::fake('public');
        $user = $this->authenticatedUser();

        $card = $this->createFakeImage('syndicate_card.jpg');

        $this->postJson('/api/v2/uploadSyndicateCard', [
            'syndicate_card' => $card,
        ]);

        $user->refresh();
        $this->assertNotNull($user->syndicate_card);
    }

    // ==================== FCM TOKEN TESTS ====================

    /** @test */
    public function test_store_fcm_token_successfully()
    {
        $user = $this->authenticatedUser();
        $fcmToken = 'fcm_token_' . fake()->uuid();

        $response = $this->postJson('/api/v2/storeFCM', [
            'token' => $fcmToken,
        ]);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('fcm_tokens', [
            'doctor_id' => $user->id,
            'token' => $fcmToken,
        ]);
    }

    /** @test */
    public function test_store_fcm_token_requires_authentication()
    {
        $response = $this->postJson('/api/v2/storeFCM', [
            'token' => 'test_token',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_store_fcm_token_updates_existing_token()
    {
        $user = $this->authenticatedUser();
        $oldToken = 'old_token_' . fake()->uuid();
        $newToken = 'new_token_' . fake()->uuid();

        // Store initial token
        $this->postJson('/api/v2/storeFCM', [
            'token' => $oldToken,
        ]);

        // Update with new token
        $this->postJson('/api/v2/storeFCM', [
            'token' => $newToken,
        ]);

        // Should have the new token
        $this->assertDatabaseHas('fcm_tokens', [
            'doctor_id' => $user->id,
            'token' => $newToken,
        ]);
    }

    /** @test */
    public function test_store_fcm_token_validates_required_fields()
    {
        $user = $this->authenticatedUser();

        $response = $this->postJson('/api/v2/storeFCM', []);

        $this->assertValidationError($response, ['token']);
    }

    /** @test */
    public function test_fcm_token_associates_with_correct_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $token1 = 'token1_' . fake()->uuid();
        $token2 = 'token2_' . fake()->uuid();

        // Store token for user1
        $this->actingAs($user1);
        $this->postJson('/api/v2/storeFCM', ['token' => $token1]);

        // Store token for user2
        $this->actingAs($user2);
        $this->postJson('/api/v2/storeFCM', ['token' => $token2]);

        // Verify each token is associated with the correct user
        $this->assertDatabaseHas('fcm_tokens', [
            'doctor_id' => $user1->id,
            'token' => $token1,
        ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'doctor_id' => $user2->id,
            'token' => $token2,
        ]);
    }
}
