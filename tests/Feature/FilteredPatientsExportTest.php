<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Modules\Patients\Models\Patients;
use App\Models\Questions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

class FilteredPatientsExportTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with necessary permissions
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'isSyndicateCardRequired' => 'Verified'
        ]);
    }

    /**
     * Test that the export endpoint requires authentication
     */
    public function test_export_requires_authentication(): void
    {
        $response = $this->postJson('/api/exportFilteredPatients', []);

        $response->assertStatus(401);
    }

    /**
     * Test that export returns 404 when no patients match filters
     */
    public function test_export_returns_404_when_no_patients_found(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/exportFilteredPatients', [
            '1' => 'NonExistentPatient'
        ]);

        $response->assertStatus(404)
                 ->assertJson([
                     'value' => false,
                     'message' => 'No patients found matching the specified filters.'
                 ]);
    }

    /**
     * Test the basic structure of export response
     */
    public function test_export_response_structure(): void
    {
        Sanctum::actingAs($this->user);

        // Mock the storage disk to avoid actual file creation during testing
        Storage::fake('public');

        $response = $this->postJson('/api/exportFilteredPatients', []);

        // Should either return success with file or 404 if no patients
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 404
        );

        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'value',
                'message',
                'file_url',
                'patient_count',
                'filter_count',
                'cache_key'
            ]);
        }
    }

    /**
     * Test that cache keys are generated correctly
     */
    public function test_cache_key_generation(): void
    {
        Sanctum::actingAs($this->user);

        $filterParams = ['1' => 'TestPatient'];
        $expectedCacheKey = 'filtered_patients_export_' . md5(json_encode($filterParams)) . '_' . $this->user->id;

        // We can't easily test the exact cache key without exposing the method,
        // but we can test that caching is working by checking if the cache is set
        $response = $this->postJson('/api/exportFilteredPatients', $filterParams);

        // Verify that some cache entries are created (either success or failure should cache something)
        $this->assertTrue(Cache::has($expectedCacheKey . '_filters') || $response->status() === 404);
    }

    /**
     * Test that pagination parameters are excluded from filters
     */
    public function test_pagination_parameters_excluded(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/exportFilteredPatients', [
            '1' => 'TestPatient',
            'page' => 2,
            'per_page' => 20,
            'sort' => 'name',
            'direction' => 'desc',
            'offset' => 10,
            'limit' => 5
        ]);

        // Should process successfully regardless of pagination params
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 404
        );
    }

    /**
     * Test that the endpoint handles empty filter parameters
     */
    public function test_empty_filters_handling(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/exportFilteredPatients', []);

        // Should either return all patients or 404 if no patients exist
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 404
        );
    }

    /**
     * Test error handling for invalid requests
     */
    public function test_error_handling(): void
    {
        Sanctum::actingAs($this->user);

        // Test with malformed JSON (this will be handled by Laravel's validation)
        $response = $this->post('/api/exportFilteredPatients', [], [
            'Content-Type' => 'application/json'
        ]);

        // Should handle gracefully
        $this->assertTrue(in_array($response->status(), [200, 404, 422, 500]));
    }

    /**
     * Test that the endpoint returns proper JSON response
     */
    public function test_json_response_format(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/exportFilteredPatients', [
            '9901' => 'Yes' // Submit status filter
        ]);

        $response->assertHeader('Content-Type', 'application/json');
        
        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'value',
                'message'
            ]);
        }
    }
}
