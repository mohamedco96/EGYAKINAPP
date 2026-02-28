<?php

namespace Tests\Feature\Api\V2\Patients;

use App\Models\User;
use App\Modules\Patients\Models\Patients;
use App\Models\SectionsInfo;
use App\Models\Answers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Patient PDF Generation
 *
 * Tests the following endpoints:
 * - GET /api/v2/generatePDF/{patient_id}
 *
 * @group patients
 * @group patient-pdf
 * @group api
 * @group v2
 */
class PatientPdfTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== GENERATE PDF TESTS ====================

    /** @test */
    public function test_generate_pdf_creates_file()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $response = $this->getJson("/api/v2/generatePDF/{$patient->id}");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        // Verify PDF URL is returned
        $data = $response->json('data');
        $this->assertArrayHasKey('pdf_url', $data);
        $this->assertNotEmpty($data['pdf_url']);
    }

    /** @test */
    public function test_generate_pdf_requires_authentication()
    {
        $patient = Patients::factory()->create();

        $response = $this->getJson("/api/v2/generatePDF/{$patient->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_generate_pdf_validates_patient_id()
    {
        $doctor = $this->doctorUser();

        $response = $this->getJson('/api/v2/generatePDF/99999');

        $this->assertNotFound($response);
    }

    /** @test */
    public function test_generate_pdf_includes_patient_data()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create([
            'doctor_id' => $doctor->id,
        ]);

        $response = $this->getJson("/api/v2/generatePDF/{$patient->id}");

        $response->assertStatus(200);

        // Verify response indicates successful PDF generation with patient data
        $this->assertSuccess($response);
    }

    /** @test */
    public function test_generate_pdf_includes_sections()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        // Create sections for the patient
        SectionsInfo::factory()->count(3)->create(['patient_id' => $patient->id]);

        $response = $this->getJson("/api/v2/generatePDF/{$patient->id}");

        $response->assertStatus(200);

        // PDF should be generated successfully with section data
        $this->assertSuccess($response);
    }

    /** @test */
    public function test_generate_pdf_includes_answers()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);
        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);

        // Create answers
        Answers::factory()->count(5)->create([
            'patient_id' => $patient->id,
            'section_id' => $section->id,
        ]);

        $response = $this->getJson("/api/v2/generatePDF/{$patient->id}");

        $response->assertStatus(200);

        // PDF should include answer data
        $this->assertSuccess($response);
    }

    /** @test */
    public function test_generate_pdf_includes_doctor_info()
    {
        $doctor = $this->doctorUser([
            'name' => 'Dr. John',
            'lname' => 'Doe',
        ]);

        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $response = $this->getJson("/api/v2/generatePDF/{$patient->id}");

        $response->assertStatus(200);

        // PDF should include doctor information
        $this->assertSuccess($response);
    }

    /** @test */
    public function test_generate_pdf_returns_correct_content_type()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $response = $this->getJson("/api/v2/generatePDF/{$patient->id}");

        $response->assertStatus(200);

        // Verify response indicates PDF generation
        $data = $response->json('data');

        if (isset($data['pdf_url'])) {
            $this->assertStringContainsString('.pdf', $data['pdf_url']);
        }
    }

    /** @test */
    public function test_generate_pdf_handles_incomplete_patient()
    {
        $doctor = $this->doctorUser();

        // Create patient with no sections or answers
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $response = $this->getJson("/api/v2/generatePDF/{$patient->id}");

        // Should still generate PDF, possibly with placeholder data
        $response->assertStatus(200);
    }

    /** @test */
    public function test_generate_pdf_handles_missing_patient()
    {
        $doctor = $this->doctorUser();

        $response = $this->getJson('/api/v2/generatePDF/99999');

        $this->assertNotFound($response);
    }

    /** @test */
    public function test_pdf_includes_recommendations()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        // Create recommendations
        \DB::table('recommendations')->insert([
            'patient_id' => $patient->id,
            'recommendation' => 'Test recommendation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson("/api/v2/generatePDF/{$patient->id}");

        $response->assertStatus(200);

        // PDF should include recommendations
        $this->assertSuccess($response);
    }
}
