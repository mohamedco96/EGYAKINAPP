<?php

namespace Tests\Traits;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

trait ApiTestHelpers
{
    /**
     * Create and authenticate a user
     */
    protected function authenticatedUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        Sanctum::actingAs($user);
        return $user;
    }

    /**
     * Create a verified user with email verified
     */
    protected function verifiedUser(array $attributes = []): User
    {
        return $this->authenticatedUser(array_merge([
            'email_verified_at' => now(),
        ], $attributes));
    }

    /**
     * Create a doctor user with role
     */
    protected function doctorUser(array $attributes = []): User
    {
        $user = $this->verifiedUser(array_merge([
            'user_type' => 'medical_statistics',
        ], $attributes));

        $role = Role::firstOrCreate(['name' => 'doctor']);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Create a normal user with default role
     */
    protected function normalUser(array $attributes = []): User
    {
        $user = $this->verifiedUser(array_merge([
            'user_type' => 'normal',
        ], $attributes));

        $role = Role::firstOrCreate(['name' => 'user']);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Assert JSON response has pagination structure
     */
    protected function assertHasPaginationStructure(TestResponse $response): void
    {
        $response->assertJsonStructure([
            'value',
            'data' => [
                'current_page',
                'data',
                'per_page',
                'total',
                'last_page',
            ],
        ]);
    }

    /**
     * Assert unauthenticated response (401)
     */
    protected function assertUnauthenticated(TestResponse $response): void
    {
        $response->assertStatus(401);
    }

    /**
     * Assert unauthorized response (403)
     */
    protected function assertUnauthorized(TestResponse $response): void
    {
        $response->assertStatus(403);
    }

    /**
     * Assert not found response (404)
     */
    protected function assertNotFound(TestResponse $response): void
    {
        $response->assertStatus(404);
    }

    /**
     * Assert validation error response (422)
     */
    protected function assertValidationError(TestResponse $response, ?array $fields = null): void
    {
        $response->assertStatus(422);

        if ($fields) {
            $response->assertJsonValidationErrors($fields);
        }
    }

    /**
     * Assert successful response with value=true
     */
    protected function assertSuccess(TestResponse $response): void
    {
        $response->assertStatus(200)
            ->assertJson(['value' => true]);
    }

    /**
     * Create fake image file for upload testing
     */
    protected function createFakeImage(string $name = 'test.jpg', int $width = 600, int $height = 400): UploadedFile
    {
        Storage::fake('public');
        return UploadedFile::fake()->image($name, $width, $height);
    }

    /**
     * Create fake document file for upload testing
     */
    protected function createFakeDocument(string $name = 'test.pdf', int $kilobytes = 100): UploadedFile
    {
        Storage::fake('public');
        return UploadedFile::fake()->create($name, $kilobytes, 'application/pdf');
    }

    /**
     * Assert response has success structure with data
     */
    protected function assertSuccessWithData(TestResponse $response, array $dataStructure = []): void
    {
        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        if (!empty($dataStructure)) {
            $response->assertJsonStructure(array_merge(['value', 'message'], ['data' => $dataStructure]));
        }
    }

    /**
     * Get JSON response data
     */
    protected function getResponseData(TestResponse $response): array
    {
        return $response->json('data', []);
    }

    /**
     * Assert database has user with specific attributes
     */
    protected function assertUserExists(array $attributes): void
    {
        $this->assertDatabaseHas('users', $attributes);
    }

    /**
     * Assert database does not have user with specific attributes
     */
    protected function assertUserDoesNotExist(array $attributes): void
    {
        $this->assertDatabaseMissing('users', $attributes);
    }
}
