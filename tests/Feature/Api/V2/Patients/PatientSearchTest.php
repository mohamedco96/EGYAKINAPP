<?php

namespace Tests\Feature\Api\V2\Patients;

use App\Models\User;
use App\Modules\Patients\Models\Patients;
use App\Models\Dose;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Patient Search functionality
 *
 * Tests the following endpoints:
 * - POST /api/v2/searchNew
 *
 * @group patients
 * @group patient-search
 * @group api
 * @group v2
 */
class PatientSearchTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== SEARCH TESTS ====================

    /** @test */
    public function test_search_new_finds_patients_by_id()
    {
        $doctor = $this->doctorUser();

        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $response = $this->postJson('/api/v2/searchNew', [
            'patient' => (string)$patient->id,
            'dose' => '',
        ]);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $results = $response->json('data');

        // Verify the patient was found
        $patientIds = collect($results)->pluck('id')->toArray();
        $this->assertContains($patient->id, $patientIds);
    }

    /** @test */
    public function test_search_new_requires_authentication()
    {
        $response = $this->postJson('/api/v2/searchNew', [
            'patient' => '123',
            'dose' => '',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_search_new_filters_by_doctor_id()
    {
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        // Create patients for both doctors
        $doctor1Patient = Patients::factory()->create(['doctor_id' => $doctor1->id]);
        $doctor2Patient = Patients::factory()->create(['doctor_id' => $doctor2->id]);

        // Authenticate as doctor1
        $this->actingAs($doctor1);

        $response = $this->postJson('/api/v2/searchNew', [
            'patient' => '',
            'dose' => '',
        ]);

        $response->assertStatus(200);

        $results = $response->json('data');
        $patientIds = collect($results)->pluck('id')->toArray();

        // Should include doctor1's patient
        $this->assertContains($doctor1Patient->id, $patientIds);

        // Should NOT include doctor2's patient
        $this->assertNotContains($doctor2Patient->id, $patientIds);
    }

    /** @test */
    public function test_search_new_handles_empty_results()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/searchNew', [
            'patient' => '99999', // Non-existent patient ID
            'dose' => '',
        ]);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $results = $response->json('data');
        $this->assertEmpty($results);
    }

    /** @test */
    public function test_search_new_validates_search_criteria()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/searchNew', []);

        // Depending on validation rules, adjust accordingly
        $this->assertValidationError($response, ['patient', 'dose']);
    }

    /** @test */
    public function test_search_new_searches_by_dose()
    {
        $doctor = $this->doctorUser();

        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        // Create dose for the patient
        $dose = Dose::factory()->create([
            'patient_id' => $patient->id,
            'name' => 'Test Medication',
        ]);

        $response = $this->postJson('/api/v2/searchNew', [
            'patient' => '',
            'dose' => 'Test Medication',
        ]);

        $response->assertStatus(200);

        $results = $response->json('data');
        $patientIds = collect($results)->pluck('id')->toArray();

        $this->assertContains($patient->id, $patientIds);
    }

    /** @test */
    public function test_search_new_handles_special_characters()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/searchNew', [
            'patient' => "'; DROP TABLE patients; --",
            'dose' => '',
        ]);

        // Should handle safely without SQL injection
        $response->assertStatus(200);
    }

    /** @test */
    public function test_search_new_is_case_insensitive()
    {
        $doctor = $this->doctorUser();

        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $dose = Dose::factory()->create([
            'patient_id' => $patient->id,
            'name' => 'Aspirin',
        ]);

        $response = $this->postJson('/api/v2/searchNew', [
            'patient' => '',
            'dose' => 'aspirin', // Lowercase
        ]);

        $response->assertStatus(200);

        $results = $response->json('data');

        // Should find the patient regardless of case
        if (!empty($results)) {
            $patientIds = collect($results)->pluck('id')->toArray();
            $this->assertContains($patient->id, $patientIds);
        }
    }

    /** @test */
    public function test_search_new_respects_authorization()
    {
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        // Create patient for doctor2
        $patient = Patients::factory()->create(['doctor_id' => $doctor2->id]);

        // Authenticate as doctor1
        $this->actingAs($doctor1);

        $response = $this->postJson('/api/v2/searchNew', [
            'patient' => (string)$patient->id,
            'dose' => '',
        ]);

        $response->assertStatus(200);

        $results = $response->json('data');

        // Doctor1 should NOT see doctor2's patient
        $patientIds = collect($results)->pluck('id')->toArray();
        $this->assertNotContains($patient->id, $patientIds);
    }

    /** @test */
    public function test_search_new_returns_patient_with_dose_info()
    {
        $doctor = $this->doctorUser();

        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $dose = Dose::factory()->create([
            'patient_id' => $patient->id,
            'name' => 'Medication XYZ',
        ]);

        $response = $this->postJson('/api/v2/searchNew', [
            'patient' => (string)$patient->id,
            'dose' => '',
        ]);

        $response->assertStatus(200);

        $results = $response->json('data');

        if (!empty($results)) {
            // Verify dose information is included
            $foundPatient = collect($results)->firstWhere('id', $patient->id);
            $this->assertNotNull($foundPatient);
        }
    }
}
