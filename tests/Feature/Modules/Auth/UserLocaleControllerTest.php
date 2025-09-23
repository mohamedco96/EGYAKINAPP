<?php

namespace Tests\Feature\Modules\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserLocaleControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'locale' => 'en', // Default locale
        ]);
    }

    /** @test */
    public function it_can_update_user_locale()
    {
        Sanctum::actingAs($this->user);

        $localeData = [
            'locale' => 'ar',
        ];

        $response = $this->postJson('/api/v1/user/locale', $localeData);

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'message' => 'Locale updated successfully',
                'data' => [
                    'locale' => 'ar',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'locale' => 'ar',
        ]);
    }

    /** @test */
    public function it_validates_locale_format()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/user/locale', [
            'locale' => 'invalid_locale',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    }

    /** @test */
    public function it_requires_locale_field()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/user/locale', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    }

    /** @test */
    public function it_accepts_valid_locales()
    {
        Sanctum::actingAs($this->user);

        $validLocales = ['en', 'ar', 'fr', 'es'];

        foreach ($validLocales as $locale) {
            $response = $this->postJson('/api/v1/user/locale', [
                'locale' => $locale,
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'value' => true,
                    'data' => [
                        'locale' => $locale,
                    ],
                ]);
        }
    }

    /** @test */
    public function it_can_get_user_locale()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/user/locale');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'value',
                'data' => [
                    'locale',
                    'user_id',
                ],
            ])
            ->assertJson([
                'value' => true,
                'data' => [
                    'locale' => $this->user->locale,
                    'user_id' => $this->user->id,
                ],
            ]);
    }

    /** @test */
    public function it_can_test_locale_response()
    {
        Sanctum::actingAs($this->user);

        // Update user locale to Arabic
        $this->user->update(['locale' => 'ar']);

        $response = $this->getJson('/api/v1/user/locale/test');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'value',
                'message',
                'data' => [
                    'user_locale',
                    'app_locale',
                    'localized_message',
                ],
            ])
            ->assertJson([
                'value' => true,
                'data' => [
                    'user_locale' => 'ar',
                    'app_locale' => 'ar',
                ],
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_locale_endpoints()
    {
        $endpoints = [
            ['method' => 'post', 'uri' => '/api/v1/user/locale'],
            ['method' => 'get', 'uri' => '/api/v1/user/locale'],
            ['method' => 'get', 'uri' => '/api/v1/user/locale/test'],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->{$endpoint['method'].'Json'}($endpoint['uri']);

            $response->assertStatus(401);
        }
    }

    /** @test */
    public function it_handles_locale_update_errors_gracefully()
    {
        Sanctum::actingAs($this->user);

        // Simulate database error by using invalid data
        $response = $this->postJson('/api/v1/user/locale', [
            'locale' => str_repeat('x', 300), // Too long
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_maintains_locale_persistence_across_requests()
    {
        Sanctum::actingAs($this->user);

        // Set locale to Arabic
        $this->postJson('/api/v1/user/locale', ['locale' => 'ar']);

        // Verify it persists
        $response = $this->getJson('/api/v1/user/locale');

        $response->assertJson([
            'data' => [
                'locale' => 'ar',
            ],
        ]);

        // Change to English
        $this->postJson('/api/v1/user/locale', ['locale' => 'en']);

        // Verify change persists
        $response = $this->getJson('/api/v1/user/locale');

        $response->assertJson([
            'data' => [
                'locale' => 'en',
            ],
        ]);
    }

    /** @test */
    public function it_returns_default_locale_for_new_users()
    {
        $newUser = User::factory()->create(['locale' => null]);
        Sanctum::actingAs($newUser);

        $response = $this->getJson('/api/v1/user/locale');

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'data' => [
                    'locale' => 'en', // Default fallback
                ],
            ]);
    }
}
