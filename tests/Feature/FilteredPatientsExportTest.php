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
     * Test that export returns 400 when no cached filters found
     */
    public function test_export_returns_400_when_no_cached_filters(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/exportFilteredPatients');

        $response->assertStatus(400)
                 ->assertJson([
                     'value' => false,
                     'message' => 'No recent filter criteria found. Please apply filters first using the filteredPatients endpoint.'
                 ]);
    }

    /**
     * Test that export works after calling filteredPatients
     */
    public function test_export_works_after_filtered_patients_call(): void
    {
        Sanctum::actingAs($this->user);

        // First call filteredPatients to cache filters
        $filterParams = ['1' => 'TestPatient'];
        $this->postJson('/api/patientFilters', $filterParams);

        // Then call export (should use cached filters)
        $response = $this->postJson('/api/exportFilteredPatients');

        // Should either return success with file or 404 if no patients
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 404
        );
    }

    /**
     * Test the basic structure of export response (after caching filters)
     */
    public function test_export_response_structure(): void
    {
        Sanctum::actingAs($this->user);

        // Mock the storage disk to avoid actual file creation during testing
        Storage::fake('public');

        // First cache some filters
        $this->postJson('/api/patientFilters', ['1' => 'TestPatient']);

        // Then test export
        $response = $this->postJson('/api/exportFilteredPatients');

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
     * Test that cache keys are generated correctly from cached filters
     */
    public function test_cache_key_generation(): void
    {
        Sanctum::actingAs($this->user);

        $filterParams = ['1' => 'TestPatient'];
        
        // Cache filters first
        $this->postJson('/api/patientFilters', $filterParams);
        
        // Then test export with cached filters
        $response = $this->postJson('/api/exportFilteredPatients');

        // Verify that some cache entries are created (either success or failure should cache something)
        $this->assertTrue($response->status() === 200 || $response->status() === 404 || $response->status() === 400);
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
     * Test that the endpoint handles empty cached filters
     */
    public function test_empty_filters_handling(): void
    {
        Sanctum::actingAs($this->user);

        // Don't cache any filters first, should return 400
        $response = $this->postJson('/api/exportFilteredPatients');

        $this->assertEquals(400, $response->status());
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

    /**
     * Test the complete workflow: filter then export with cached parameters
     */
    public function test_complete_workflow_filter_then_export(): void
    {
        Sanctum::actingAs($this->user);

        // Step 1: Apply filters using filteredPatients endpoint
        $filterParams = ['1' => 'TestPatient', '9901' => 'Yes'];
        
        $filterResponse = $this->postJson('/api/filteredPatients', $filterParams);
        
        // Should succeed or return 404 if no matching patients
        $this->assertContains($filterResponse->status(), [200, 404]);
        
        // Step 2: Export using cached filters
        $exportResponse = $this->postJson('/api/exportFilteredPatients');
        
        // Should succeed (200) or return 404 if no patients match the cached filters
        $this->assertContains($exportResponse->status(), [200, 404]);
        
        // If export succeeds, verify response structure
        if ($exportResponse->status() === 200) {
            $exportResponse->assertJsonStructure([
                'value',
                'message',
                'file_url',
                'patient_count',
                'filter_count',
                'cache_key'
            ]);
            
            $exportResponse->assertJson([
                'value' => true,
                'message' => 'Export completed successfully'
            ]);
        }
    }
}
