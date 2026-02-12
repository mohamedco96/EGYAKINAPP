<?php

namespace Tests\Feature\Api\V2\Patients;

use App\Models\User;
use App\Modules\Patients\Models\Patients;
use App\Models\SectionsInfo;
use App\Models\Answers;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Patient CRUD operations
 *
 * Tests the following endpoints:
 * - POST /api/v2/patient
 * - GET /api/v2/patient/{section_id}/{patient_id}
 * - PUT /api/v2/patientsection/{section_id}/{patient_id}
 * - DELETE /api/v2/patient/{id}
 * - PUT /api/v2/submitStatus/{patient_id}
 *
 * @group patients
 * @group patient-crud
 * @group api
 * @group v2
 */
class PatientCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== CREATE PATIENT TESTS ====================

    /** @test */
    public function test_create_patient_successfully()
    {
        $doctor = $this->doctorUser();

        $patientData = [
            'doctor_id' => $doctor->id,
        ];

        $response = $this->postJson('/api/v2/patient', $patientData);

        $response->assertStatus(201)
            ->assertJson(['value' => true])
            ->assertJsonStructure([
                'value',
                'message',
                'data' => ['patient' => ['id', 'doctor_id']],
            ]);

        $this->assertDatabaseHas('patients', [
            'doctor_id' => $doctor->id,
        ]);
    }

    /** @test */
    public function test_create_patient_requires_authentication()
    {
        $response = $this->postJson('/api/v2/patient', [
            'doctor_id' => 1,
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_create_patient_validates_required_fields()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/patient', []);

        // Depending on validation rules, adjust accordingly
        $response->assertStatus(422);
    }

    /** @test */
    public function test_create_patient_assigns_to_authenticated_doctor()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/patient', [
            'doctor_id' => $doctor->id,
        ]);

        $response->assertStatus(201);

        $patient = Patients::latest()->first();
        $this->assertEquals($doctor->id, $patient->doctor_id);
    }

    /** @test */
    public function test_create_patient_initializes_hidden_as_false()
    {
        $doctor = $this->doctorUser();

        $this->postJson('/api/v2/patient', [
            'doctor_id' => $doctor->id,
        ]);

        $patient = Patients::latest()->first();
        $this->assertFalse($patient->hidden);
    }

    // ==================== SHOW PATIENT SECTION TESTS ====================

    /** @test */
    public function test_show_patient_section_returns_data()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);

        $response = $this->getJson("/api/v2/patient/{$section->id}/{$patient->id}");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);
    }

    /** @test */
    public function test_show_patient_section_requires_authentication()
    {
        $patient = Patients::factory()->create();
        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);

        $response = $this->getJson("/api/v2/patient/{$section->id}/{$patient->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_show_patient_section_validates_section_and_patient_id()
    {
        $doctor = $this->doctorUser();

        $response = $this->getJson('/api/v2/patient/99999/99999');

        $this->assertNotFound($response);
    }

    // ==================== UPDATE PATIENT SECTION TESTS ====================

    /** @test */
    public function test_update_patient_section_successfully()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);
        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);

        $updateData = [
            'data' => ['key' => 'value'],
        ];

        $response = $this->putJson("/api/v2/patientsection/{$section->id}/{$patient->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);
    }

    /** @test */
    public function test_update_patient_section_requires_authentication()
    {
        $patient = Patients::factory()->create();
        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);

        $response = $this->putJson("/api/v2/patientsection/{$section->id}/{$patient->id}", [
            'data' => ['key' => 'value'],
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_update_patient_section_validates_data()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);
        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);

        $response = $this->putJson("/api/v2/patientsection/{$section->id}/{$patient->id}", []);

        // Depending on validation rules
        $response->assertStatus(422);
    }

    /** @test */
    public function test_update_patient_requires_ownership()
    {
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        // Patient belongs to doctor2
        $patient = Patients::factory()->create(['doctor_id' => $doctor2->id]);
        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);

        // Authenticate as doctor1
        $this->actingAs($doctor1);

        $response = $this->putJson("/api/v2/patientsection/{$section->id}/{$patient->id}", [
            'data' => ['key' => 'value'],
        ]);

        // Should fail due to ownership mismatch
        $response->assertStatus(403);
    }

    // ==================== FINAL SUBMIT TESTS ====================

    /** @test */
    public function test_submit_status_updates_successfully()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $response = $this->putJson("/api/v2/submitStatus/{$patient->id}");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        // Verify status was updated
        $this->assertDatabaseHas('patient_statuses', [
            'patient_id' => $patient->id,
            'key' => 'submit_status',
            'value' => 'submitted',
        ]);
    }

    /** @test */
    public function test_submit_status_requires_authentication()
    {
        $patient = Patients::factory()->create();

        $response = $this->putJson("/api/v2/submitStatus/{$patient->id}");

        $this->assertUnauthenticated($response);
    }

    // ==================== DELETE PATIENT TESTS ====================

    /** @test */
    public function test_delete_patient_successfully()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $response = $this->deleteJson("/api/v2/patient/{$patient->id}");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseMissing('patients', [
            'id' => $patient->id,
        ]);
    }

    /** @test */
    public function test_delete_patient_requires_authentication()
    {
        $patient = Patients::factory()->create();

        $response = $this->deleteJson("/api/v2/patient/{$patient->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_delete_patient_requires_ownership()
    {
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        // Patient belongs to doctor2
        $patient = Patients::factory()->create(['doctor_id' => $doctor2->id]);

        // Authenticate as doctor1
        $this->actingAs($doctor1);

        $response = $this->deleteJson("/api/v2/patient/{$patient->id}");

        // Should fail due to ownership
        $response->assertStatus(403);
    }

    /** @test */
    public function test_delete_patient_handles_non_existent_id()
    {
        $doctor = $this->doctorUser();

        $response = $this->deleteJson('/api/v2/patient/99999');

        $this->assertNotFound($response);
    }

    /** @test */
    public function test_patient_cascade_deletes_related_data()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        // Create related data
        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);
        $comment = Comment::factory()->create(['patient_id' => $patient->id]);

        $this->deleteJson("/api/v2/patient/{$patient->id}");

        // Verify related data was also deleted
        $this->assertDatabaseMissing('sections_infos', ['id' => $section->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    /** @test */
    public function test_patient_hidden_flag_works_correctly()
    {
        $doctor = $this->doctorUser();

        // Create hidden patient
        $hiddenPatient = Patients::factory()->hidden()->create([
            'doctor_id' => $doctor->id,
        ]);

        $this->assertTrue($hiddenPatient->hidden);

        // Create visible patient
        $visiblePatient = Patients::factory()->create([
            'doctor_id' => $doctor->id,
        ]);

        $this->assertFalse($visiblePatient->hidden);
    }
}
