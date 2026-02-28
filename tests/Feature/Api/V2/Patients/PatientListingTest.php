<?php

namespace Tests\Feature\Api\V2\Patients;

use App\Models\User;
use App\Modules\Patients\Models\Patients;
use App\Models\FeedPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Patient Listing endpoints
 *
 * Tests the following endpoints:
 * - GET /api/v2/homeNew
 * - GET /api/v2/allPatientsNew?page={page}
 * - GET /api/v2/currentPatientsNew?page={page}
 *
 * @group patients
 * @group patient-listing
 * @group api
 * @group v2
 */
class PatientListingTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== HOME NEW TESTS ====================

    /** @test */
    public function test_home_new_returns_feed_data()
    {
        $doctor = $this->doctorUser();

        // Create some patients and posts
        Patients::factory()->count(3)->create(['doctor_id' => $doctor->id]);
        FeedPost::factory()->count(2)->create(['doctor_id' => $doctor->id]);

        $response = $this->getJson('/api/v2/homeNew');

        $response->assertStatus(200)
            ->assertJson(['value' => true])
            ->assertJsonStructure([
                'value',
                'data',
            ]);
    }

    /** @test */
    public function test_home_new_requires_authentication()
    {
        $response = $this->getJson('/api/v2/homeNew');

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_home_new_includes_posts_and_patients()
    {
        $doctor = $this->doctorUser();

        // Create test data
        Patients::factory()->count(2)->create(['doctor_id' => $doctor->id]);
        FeedPost::factory()->count(3)->create(['doctor_id' => $doctor->id]);

        $response = $this->getJson('/api/v2/homeNew');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify response includes both posts and patients
        $this->assertArrayHasKey('posts', $data);
        $this->assertArrayHasKey('patients', $data);
    }

    /** @test */
    public function test_home_new_orders_data_correctly()
    {
        $doctor = $this->doctorUser();

        // Create patients with different timestamps
        Patients::factory()->create([
            'doctor_id' => $doctor->id,
            'updated_at' => now()->subDays(3),
        ]);

        Patients::factory()->create([
            'doctor_id' => $doctor->id,
            'updated_at' => now()->subDays(1),
        ]);

        $response = $this->getJson('/api/v2/homeNew');

        $response->assertStatus(200);

        $patients = $response->json('data.patients');

        // Verify newest patients appear first
        if (count($patients) >= 2) {
            $this->assertGreaterThanOrEqual(
                strtotime($patients[1]['updated_at']),
                strtotime($patients[0]['updated_at'])
            );
        }
    }

    // ==================== ALL PATIENTS NEW TESTS ====================

    /** @test */
    public function test_all_patients_new_returns_paginated_list()
    {
        $doctor = $this->doctorUser();

        // Create patients for current doctor
        Patients::factory()->count(10)->create(['doctor_id' => $doctor->id]);

        // Create patients for other doctors
        Patients::factory()->count(5)->create();

        $response = $this->getJson('/api/v2/allPatientsNew?page=1');

        $response->assertStatus(200);
        $this->assertHasPaginationStructure($response);
    }

    /** @test */
    public function test_all_patients_new_requires_authentication()
    {
        $response = $this->getJson('/api/v2/allPatientsNew');

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_all_patients_new_includes_all_doctors_patients()
    {
        $doctor = $this->doctorUser();

        // Create patients from multiple doctors
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        Patients::factory()->count(3)->create(['doctor_id' => $doctor1->id]);
        Patients::factory()->count(2)->create(['doctor_id' => $doctor2->id]);

        $response = $this->getJson('/api/v2/allPatientsNew');

        $response->assertStatus(200);

        $total = $response->json('data.total');

        // Should include patients from all doctors
        $this->assertGreaterThanOrEqual(5, $total);
    }

    /** @test */
    public function test_all_patients_new_pagination_works()
    {
        $doctor = $this->doctorUser();

        // Create 30 patients
        Patients::factory()->count(30)->create();

        // Get first page
        $page1Response = $this->getJson('/api/v2/allPatientsNew?page=1');
        $page1Response->assertStatus(200);

        // Get second page
        $page2Response = $this->getJson('/api/v2/allPatientsNew?page=2');
        $page2Response->assertStatus(200);

        $page1Data = $page1Response->json('data.data');
        $page2Data = $page2Response->json('data.data');

        // Verify different data on each page
        $this->assertNotEquals($page1Data, $page2Data);
    }

    /** @test */
    public function test_all_patients_new_excludes_hidden_patients()
    {
        $doctor = $this->doctorUser();

        // Create visible patients
        Patients::factory()->count(3)->create([
            'doctor_id' => $doctor->id,
            'hidden' => false,
        ]);

        // Create hidden patients
        Patients::factory()->count(2)->create([
            'doctor_id' => $doctor->id,
            'hidden' => true,
        ]);

        $response = $this->getJson('/api/v2/allPatientsNew');

        $response->assertStatus(200);

        $patients = $response->json('data.data');

        // Verify no hidden patients in results
        foreach ($patients as $patient) {
            if ($patient['doctor_id'] == $doctor->id) {
                $this->assertFalse($patient['hidden'] ?? false);
            }
        }
    }

    // ==================== CURRENT PATIENTS NEW TESTS ====================

    /** @test */
    public function test_current_patients_new_returns_paginated_list()
    {
        $doctor = $this->doctorUser();

        Patients::factory()->count(10)->create(['doctor_id' => $doctor->id]);

        $response = $this->getJson('/api/v2/currentPatientsNew?page=1');

        $response->assertStatus(200);
        $this->assertHasPaginationStructure($response);
    }

    /** @test */
    public function test_current_patients_new_requires_authentication()
    {
        $response = $this->getJson('/api/v2/currentPatientsNew');

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_current_patients_new_filters_by_authenticated_doctor()
    {
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        // Create patients for doctor1
        Patients::factory()->count(3)->create(['doctor_id' => $doctor1->id]);

        // Create patients for doctor2
        Patients::factory()->count(5)->create(['doctor_id' => $doctor2->id]);

        // Authenticate as doctor1
        $this->actingAs($doctor1);

        $response = $this->getJson('/api/v2/currentPatientsNew');

        $response->assertStatus(200);

        $patients = $response->json('data.data');

        // Should only return doctor1's patients
        foreach ($patients as $patient) {
            $this->assertEquals($doctor1->id, $patient['doctor_id']);
        }

        $total = $response->json('data.total');
        $this->assertEquals(3, $total);
    }

    /** @test */
    public function test_current_patients_new_only_shows_incomplete_patients()
    {
        $doctor = $this->doctorUser();

        // Create incomplete patients (without submit_status = submitted)
        Patients::factory()->count(3)->create(['doctor_id' => $doctor->id]);

        // Create completed patient
        $completedPatient = Patients::factory()->create(['doctor_id' => $doctor->id]);
        \DB::table('patient_statuses')->insert([
            'patient_id' => $completedPatient->id,
            'key' => 'submit_status',
            'value' => 'submitted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v2/currentPatientsNew');

        $response->assertStatus(200);

        $patients = $response->json('data.data');

        // Should not include completed patient
        $patientIds = collect($patients)->pluck('id')->toArray();
        $this->assertNotContains($completedPatient->id, $patientIds);
    }

    /** @test */
    public function test_current_patients_new_pagination_works()
    {
        $doctor = $this->doctorUser();

        // Create 25 patients
        Patients::factory()->count(25)->create(['doctor_id' => $doctor->id]);

        // Get first page
        $page1Response = $this->getJson('/api/v2/currentPatientsNew?page=1');
        $page1Response->assertStatus(200);

        // Get second page
        $page2Response = $this->getJson('/api/v2/currentPatientsNew?page=2');
        $page2Response->assertStatus(200);

        $page1Data = $page1Response->json('data.data');
        $page2Data = $page2Response->json('data.data');

        // Verify different patients on each page
        $this->assertNotEquals($page1Data, $page2Data);
    }

    /** @test */
    public function test_patient_lists_eager_load_relationships()
    {
        $doctor = $this->doctorUser();

        Patients::factory()->count(5)->create(['doctor_id' => $doctor->id]);

        $response = $this->getJson('/api/v2/currentPatientsNew');

        $response->assertStatus(200);

        $patients = $response->json('data.data');

        // Verify doctor relationship is loaded
        foreach ($patients as $patient) {
            $this->assertArrayHasKey('doctor', $patient);
        }
    }
}
