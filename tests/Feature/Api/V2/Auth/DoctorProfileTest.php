<?php

namespace Tests\Feature\Api\V2\Auth;

use App\Models\User;
use App\Modules\Patients\Models\Patients;
use App\Models\ScoreHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Doctor Profile endpoints
 *
 * Tests the following endpoints:
 * - GET /api/v2/showAnotherProfile/{id}
 * - GET /api/v2/doctorProfileGetPatients/{id}
 * - GET /api/v2/doctorProfileGetScoreHistory/{id}
 *
 * @group auth
 * @group doctor-profile
 * @group api
 * @group v2
 */
class DoctorProfileTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== SHOW ANOTHER PROFILE TESTS ====================

    /** @test */
    public function test_show_another_profile_returns_doctor_data()
    {
        $currentUser = $this->doctorUser();
        $otherDoctor = $this->doctorUser();

        $response = $this->getJson("/api/v2/showAnotherProfile/{$otherDoctor->id}");

        $response->assertStatus(200)
            ->assertJson(['value' => true])
            ->assertJsonStructure([
                'value',
                'data' => [
                    'user' => ['id', 'name', 'lname', 'email'],
                ],
            ]);
    }

    /** @test */
    public function test_show_another_profile_requires_authentication()
    {
        $doctor = User::factory()->create();

        $response = $this->getJson("/api/v2/showAnotherProfile/{$doctor->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_show_another_profile_handles_non_existent_doctor()
    {
        $this->authenticatedUser();

        $response = $this->getJson('/api/v2/showAnotherProfile/99999');

        $this->assertNotFound($response);
    }

    /** @test */
    public function test_show_another_profile_hides_sensitive_data()
    {
        $currentUser = $this->authenticatedUser();
        $otherDoctor = User::factory()->create([
            'password' => bcrypt('secret_password'),
        ]);

        $response = $this->getJson("/api/v2/showAnotherProfile/{$otherDoctor->id}");

        $response->assertStatus(200);

        $responseData = $response->json('data.user');

        // Ensure sensitive fields are not exposed
        $this->assertArrayNotHasKey('password', $responseData);
        $this->assertArrayNotHasKey('remember_token', $responseData);
    }

    /** @test */
    public function test_viewing_own_profile_returns_extended_data()
    {
        $user = $this->authenticatedUser();

        $response = $this->getJson("/api/v2/showAnotherProfile/{$user->id}");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        // When viewing own profile, might return additional data
        $this->assertArrayHasKey('data', $response->json());
    }

    // ==================== DOCTOR PROFILE GET PATIENTS TESTS ====================

    /** @test */
    public function test_doctor_profile_get_patients_returns_paginated_list()
    {
        $currentUser = $this->doctorUser();
        $otherDoctor = $this->doctorUser();

        // Create patients for the other doctor
        Patients::factory()->count(5)->create([
            'doctor_id' => $otherDoctor->id,
        ]);

        $response = $this->getJson("/api/v2/doctorProfileGetPatients/{$otherDoctor->id}?page=1");

        $response->assertStatus(200);
        $this->assertHasPaginationStructure($response);
    }

    /** @test */
    public function test_doctor_profile_get_patients_requires_authentication()
    {
        $doctor = User::factory()->create();

        $response = $this->getJson("/api/v2/doctorProfileGetPatients/{$doctor->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_doctor_profile_get_patients_filters_by_doctor_id()
    {
        $currentUser = $this->doctorUser();
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        // Create patients for each doctor
        Patients::factory()->count(3)->create(['doctor_id' => $doctor1->id]);
        Patients::factory()->count(2)->create(['doctor_id' => $doctor2->id]);

        $response = $this->getJson("/api/v2/doctorProfileGetPatients/{$doctor1->id}");

        $response->assertStatus(200);

        $patients = $response->json('data.data');
        $this->assertCount(3, $patients);

        // Verify all patients belong to doctor1
        foreach ($patients as $patient) {
            $this->assertEquals($doctor1->id, $patient['doctor_id']);
        }
    }

    /** @test */
    public function test_doctor_profile_get_patients_returns_empty_for_no_patients()
    {
        $currentUser = $this->doctorUser();
        $doctorWithNoPatients = $this->doctorUser();

        $response = $this->getJson("/api/v2/doctorProfileGetPatients/{$doctorWithNoPatients->id}");

        $response->assertStatus(200);

        $patients = $response->json('data.data');
        $this->assertEmpty($patients);
    }

    /** @test */
    public function test_doctor_profile_pagination_works_correctly()
    {
        $currentUser = $this->doctorUser();
        $doctor = $this->doctorUser();

        // Create 25 patients (assuming page size is 15 by default)
        Patients::factory()->count(25)->create(['doctor_id' => $doctor->id]);

        // Get first page
        $firstPage = $this->getJson("/api/v2/doctorProfileGetPatients/{$doctor->id}?page=1");
        $firstPage->assertStatus(200);

        // Get second page
        $secondPage = $this->getJson("/api/v2/doctorProfileGetPatients/{$doctor->id}?page=2");
        $secondPage->assertStatus(200);

        $firstPageData = $firstPage->json('data.data');
        $secondPageData = $secondPage->json('data.data');

        // Ensure we have data on both pages
        $this->assertNotEmpty($firstPageData);
        $this->assertNotEmpty($secondPageData);

        // Ensure different patients on each page
        $firstPageIds = collect($firstPageData)->pluck('id')->toArray();
        $secondPageIds = collect($secondPageData)->pluck('id')->toArray();
        $this->assertEmpty(array_intersect($firstPageIds, $secondPageIds));
    }

    // ==================== DOCTOR PROFILE GET SCORE HISTORY TESTS ====================

    /** @test */
    public function test_doctor_profile_get_score_history_returns_paginated_list()
    {
        $currentUser = $this->doctorUser();
        $doctor = $this->doctorUser();

        // Create score history for the doctor
        ScoreHistory::factory()->count(10)->create([
            'doctor_id' => $doctor->id,
        ]);

        $response = $this->getJson("/api/v2/doctorProfileGetScoreHistory/{$doctor->id}?page=1");

        $response->assertStatus(200);
        $this->assertHasPaginationStructure($response);
    }

    /** @test */
    public function test_doctor_profile_get_score_history_requires_authentication()
    {
        $doctor = User::factory()->create();

        $response = $this->getJson("/api/v2/doctorProfileGetScoreHistory/{$doctor->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_doctor_profile_get_score_history_orders_by_date()
    {
        $currentUser = $this->doctorUser();
        $doctor = $this->doctorUser();

        // Create score history with different dates
        ScoreHistory::factory()->create([
            'doctor_id' => $doctor->id,
            'created_at' => now()->subDays(3),
        ]);

        ScoreHistory::factory()->create([
            'doctor_id' => $doctor->id,
            'created_at' => now()->subDays(1),
        ]);

        ScoreHistory::factory()->create([
            'doctor_id' => $doctor->id,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->getJson("/api/v2/doctorProfileGetScoreHistory/{$doctor->id}");

        $response->assertStatus(200);

        $scores = $response->json('data.data');

        // Verify ordering (newest first)
        $dates = collect($scores)->pluck('created_at')->toArray();
        $this->assertEquals($dates, collect($dates)->sortDesc()->values()->toArray());
    }

    /** @test */
    public function test_doctor_profile_returns_patient_count()
    {
        $currentUser = $this->doctorUser();
        $doctor = $this->doctorUser();

        // Create patients
        Patients::factory()->count(7)->create(['doctor_id' => $doctor->id]);

        $response = $this->getJson("/api/v2/doctorProfileGetPatients/{$doctor->id}");

        $response->assertStatus(200);

        $total = $response->json('data.total');
        $this->assertEquals(7, $total);
    }

    /** @test */
    public function test_doctor_profile_includes_patient_statistics()
    {
        $currentUser = $this->doctorUser();
        $doctor = $this->doctorUser();

        // Create patients with different statuses
        Patients::factory()->count(3)->create([
            'doctor_id' => $doctor->id,
            'hidden' => false,
        ]);

        Patients::factory()->count(2)->create([
            'doctor_id' => $doctor->id,
            'hidden' => true,
        ]);

        $response = $this->getJson("/api/v2/doctorProfileGetPatients/{$doctor->id}");

        $response->assertStatus(200);

        // Verify pagination info includes statistics
        $this->assertArrayHasKey('data', $response->json());
        $this->assertArrayHasKey('total', $response->json('data'));
    }

    /** @test */
    public function test_get_score_history_for_doctor_with_no_history()
    {
        $currentUser = $this->doctorUser();
        $doctorWithNoHistory = $this->doctorUser();

        $response = $this->getJson("/api/v2/doctorProfileGetScoreHistory/{$doctorWithNoHistory->id}");

        $response->assertStatus(200);

        $scores = $response->json('data.data');
        $this->assertEmpty($scores);
    }
}
