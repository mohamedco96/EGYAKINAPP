<?php

namespace Tests\Feature\Api\V2\Settings;

use App\Modules\Settings\Models\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Settings endpoints
 *
 * Tests the following endpoints:
 * - GET /api/v2/settings
 *
 * @group settings
 * @group api
 * @group v2
 */
class SettingsTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create default settings
        Settings::create([
            'app_freeze' => false,
            'force_update' => false,
        ]);
    }

    /** @test */
    public function test_get_settings_returns_app_settings()
    {
        $response = $this->getJson('/api/v2/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'app_freeze', 'force_update'],
            ]);
    }

    /** @test */
    public function test_get_settings_does_not_require_authentication()
    {
        // No authentication setup - should still work
        $response = $this->getJson('/api/v2/settings');

        $response->assertStatus(200);
    }

    /** @test */
    public function test_settings_includes_all_configured_settings()
    {
        $response = $this->getJson('/api/v2/settings');

        $response->assertStatus(200);

        $settings = $response->json();
        $this->assertNotEmpty($settings);

        // Check that the first setting has the expected structure
        $this->assertArrayHasKey('app_freeze', $settings[0]);
        $this->assertArrayHasKey('force_update', $settings[0]);
    }

    /** @test */
    public function test_settings_returns_correct_values()
    {
        $response = $this->getJson('/api/v2/settings');

        $response->assertStatus(200);

        $settings = collect($response->json());
        $setting = $settings->first();

        $this->assertFalse($setting['app_freeze']);
        $this->assertFalse($setting['force_update']);
    }

    /** @test */
    public function test_settings_endpoint_handles_empty_settings()
    {
        // Clear all settings
        Settings::truncate();

        $response = $this->getJson('/api/v2/settings');

        $response->assertStatus(200)
            ->assertJson([]);
    }
}
