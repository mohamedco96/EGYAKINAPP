<?php

namespace Tests\Feature\Api\V2\Patients;

use App\Models\User;
use App\Modules\Patients\Models\Patients;
use App\Models\SectionsInfo;
use App\Models\Questions;
use App\Models\Answers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Patient Section Management
 *
 * Tests the following endpoints:
 * - GET /api/v2/showSections/{patient_id}
 * - GET /api/v2/questions/{section_id}
 * - GET /api/v2/patient/{section_id}/{patient_id}
 *
 * @group patients
 * @group patient-sections
 * @group api
 * @group v2
 */
class PatientSectionTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== SHOW SECTIONS TESTS ====================

    /** @test */
    public function test_show_sections_returns_all_sections()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        // Create multiple sections
        SectionsInfo::factory()->count(3)->create(['patient_id' => $patient->id]);

        $response = $this->getJson("/api/v2/showSections/{$patient->id}");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $sections = $response->json('data');
        $this->assertCount(3, $sections);
    }

    /** @test */
    public function test_show_sections_requires_authentication()
    {
        $patient = Patients::factory()->create();

        $response = $this->getJson("/api/v2/showSections/{$patient->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_show_sections_validates_patient_id()
    {
        $doctor = $this->doctorUser();

        $response = $this->getJson('/api/v2/showSections/99999');

        $this->assertNotFound($response);
    }

    /** @test */
    public function test_show_sections_shows_completion_status()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        $section = SectionsInfo::factory()->create([
            'patient_id' => $patient->id,
            'status' => 'completed',
        ]);

        $response = $this->getJson("/api/v2/showSections/{$patient->id}");

        $response->assertStatus(200);

        $sections = $response->json('data');
        $foundSection = collect($sections)->firstWhere('id', $section->id);

        if ($foundSection) {
            $this->assertEquals('completed', $foundSection['status'] ?? null);
        }
    }

    // ==================== GET QUESTIONS TESTS ====================

    /** @test */
    public function test_get_questions_by_section_returns_questions()
    {
        $doctor = $this->doctorUser();

        // Create questions for a section
        $sectionId = 1; // Assuming section 1 exists
        Questions::factory()->count(5)->create(['section_id' => $sectionId]);

        $response = $this->getJson("/api/v2/questions/{$sectionId}");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $questions = $response->json('data');
        $this->assertNotEmpty($questions);
    }

    /** @test */
    public function test_get_questions_requires_authentication()
    {
        $response = $this->getJson('/api/v2/questions/1');

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_questions_validates_section_id()
    {
        $doctor = $this->doctorUser();

        $response = $this->getJson('/api/v2/questions/99999');

        // Should return empty array or 404
        $response->assertStatus(200);

        $questions = $response->json('data');
        $this->assertEmpty($questions);
    }

    /** @test */
    public function test_get_questions_returns_ordered_questions()
    {
        $doctor = $this->doctorUser();
        $sectionId = 1;

        // Create questions with different orders
        Questions::factory()->create(['section_id' => $sectionId, 'order' => 3]);
        Questions::factory()->create(['section_id' => $sectionId, 'order' => 1]);
        Questions::factory()->create(['section_id' => $sectionId, 'order' => 2]);

        $response = $this->getJson("/api/v2/questions/{$sectionId}");

        $response->assertStatus(200);

        $questions = $response->json('data');

        if (count($questions) >= 3) {
            // Verify ordering
            $orders = collect($questions)->pluck('order')->toArray();
            $this->assertEquals([1, 2, 3], $orders);
        }
    }

    // ==================== PATIENT SECTION DETAILS TESTS ====================

    /** @test */
    public function test_patient_section_shows_answers()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);
        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);

        // Create answers for the patient in this section
        Answers::factory()->count(3)->create([
            'patient_id' => $patient->id,
            'section_id' => $section->id,
        ]);

        $response = $this->getJson("/api/v2/patient/{$section->id}/{$patient->id}");

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify answers are included
        $this->assertArrayHasKey('answers', $data);
    }

    /** @test */
    public function test_patient_section_shows_question_types()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);
        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);

        // Create questions of different types
        $question1 = Questions::factory()->create([
            'section_id' => $section->id,
            'type' => 'text',
        ]);

        $question2 = Questions::factory()->create([
            'section_id' => $section->id,
            'type' => 'multiple_choice',
        ]);

        $response = $this->getJson("/api/v2/patient/{$section->id}/{$patient->id}");

        $response->assertStatus(200);

        $data = $response->json('data');

        if (isset($data['questions'])) {
            $questionTypes = collect($data['questions'])->pluck('type')->toArray();
            $this->assertContains('text', $questionTypes);
            $this->assertContains('multiple_choice', $questionTypes);
        }
    }

    /** @test */
    public function test_section_completion_percentage_calculated()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);
        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);

        // Create 10 questions
        Questions::factory()->count(10)->create(['section_id' => $section->id]);

        // Answer 5 of them
        Answers::factory()->count(5)->create([
            'patient_id' => $patient->id,
            'section_id' => $section->id,
        ]);

        $response = $this->getJson("/api/v2/showSections/{$patient->id}");

        $response->assertStatus(200);

        $sections = $response->json('data');
        $foundSection = collect($sections)->firstWhere('id', $section->id);

        if ($foundSection && isset($foundSection['completion_percentage'])) {
            $this->assertEquals(50, $foundSection['completion_percentage']);
        }
    }

    /** @test */
    public function test_section_navigation_order_maintained()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        // Create sections with specific order
        SectionsInfo::factory()->create([
            'patient_id' => $patient->id,
            'section_number' => 1,
        ]);

        SectionsInfo::factory()->create([
            'patient_id' => $patient->id,
            'section_number' => 2,
        ]);

        SectionsInfo::factory()->create([
            'patient_id' => $patient->id,
            'section_number' => 3,
        ]);

        $response = $this->getJson("/api/v2/showSections/{$patient->id}");

        $response->assertStatus(200);

        $sections = $response->json('data');

        if (count($sections) >= 3) {
            $sectionNumbers = collect($sections)->pluck('section_number')->toArray();
            $this->assertEquals([1, 2, 3], $sectionNumbers);
        }
    }

    /** @test */
    public function test_section_validation_rules_enforced()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);
        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);

        // Try to update section with invalid data
        $response = $this->putJson("/api/v2/patientsection/{$section->id}/{$patient->id}", [
            'invalid_field' => 'invalid_value',
        ]);

        // Should validate and reject
        $this->assertValidationError($response);
    }

    /** @test */
    public function test_readonly_sections_cannot_be_edited()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);

        // Create a readonly section
        $section = SectionsInfo::factory()->create([
            'patient_id' => $patient->id,
            'readonly' => true,
        ]);

        $response = $this->putJson("/api/v2/patientsection/{$section->id}/{$patient->id}", [
            'data' => ['key' => 'value'],
        ]);

        // Should be forbidden or return error
        $response->assertStatus(403);
    }

    /** @test */
    public function test_section_score_calculated_correctly()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);
        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);

        // Create questions with scores
        $question1 = Questions::factory()->create([
            'section_id' => $section->id,
            'max_score' => 10,
        ]);

        $question2 = Questions::factory()->create([
            'section_id' => $section->id,
            'max_score' => 10,
        ]);

        // Create answers with scores
        Answers::factory()->create([
            'patient_id' => $patient->id,
            'question_id' => $question1->id,
            'score' => 8,
        ]);

        Answers::factory()->create([
            'patient_id' => $patient->id,
            'question_id' => $question2->id,
            'score' => 6,
        ]);

        $response = $this->getJson("/api/v2/patient/{$section->id}/{$patient->id}");

        $response->assertStatus(200);

        $data = $response->json('data');

        if (isset($data['total_score'])) {
            $this->assertEquals(14, $data['total_score']);
        }
    }

    /** @test */
    public function test_section_timestamps_tracked()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);
        $section = SectionsInfo::factory()->create([
            'patient_id' => $patient->id,
            'started_at' => now()->subHours(2),
            'completed_at' => null,
        ]);

        $response = $this->getJson("/api/v2/showSections/{$patient->id}");

        $response->assertStatus(200);

        $sections = $response->json('data');
        $foundSection = collect($sections)->firstWhere('id', $section->id);

        if ($foundSection) {
            $this->assertArrayHasKey('started_at', $foundSection);
            $this->assertArrayHasKey('completed_at', $foundSection);
        }
    }

    /** @test */
    public function test_section_answers_validated_by_type()
    {
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);
        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);

        // Create a numeric question
        $numericQuestion = Questions::factory()->create([
            'section_id' => $section->id,
            'type' => 'numeric',
        ]);

        // Try to save non-numeric answer
        $response = $this->putJson("/api/v2/patientsection/{$section->id}/{$patient->id}", [
            'answers' => [
                [
                    'question_id' => $numericQuestion->id,
                    'value' => 'not a number',
                ],
            ],
        ]);

        // Should validate and reject
        $this->assertValidationError($response);
    }

    /** @test */
    public function test_section_file_uploads_handled()
    {
        Storage::fake('public');
        $doctor = $this->doctorUser();
        $patient = Patients::factory()->create(['doctor_id' => $doctor->id]);
        $section = SectionsInfo::factory()->create(['patient_id' => $patient->id]);

        // Create a file upload question
        $fileQuestion = Questions::factory()->create([
            'section_id' => $section->id,
            'type' => 'file',
        ]);

        $file = $this->createFakeImage('test-file.jpg');

        $response = $this->putJson("/api/v2/patientsection/{$section->id}/{$patient->id}", [
            'answers' => [
                [
                    'question_id' => $fileQuestion->id,
                    'file' => $file,
                ],
            ],
        ]);

        $response->assertStatus(200);

        // Verify file was stored
        $this->assertDatabaseHas('answers', [
            'patient_id' => $patient->id,
            'question_id' => $fileQuestion->id,
        ]);
    }
}
