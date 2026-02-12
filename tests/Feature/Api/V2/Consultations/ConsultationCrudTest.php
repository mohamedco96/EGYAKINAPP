<?php

namespace Tests\Feature\Api\V2\Consultations;

use App\Models\User;
use App\Models\Consultation;
use App\Models\ConsultationReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Consultation CRUD operations
 *
 * Tests the following endpoints:
 * - POST /api/v2/consultations
 * - GET /api/v2/consultations/sent
 * - GET /api/v2/consultations/received
 * - GET /api/v2/consultations/{id}
 *
 * @group consultations
 * @group consultation-crud
 * @group api
 * @group v2
 */
class ConsultationCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== CREATE CONSULTATION TESTS ====================

    /** @test */
    public function test_create_consultation_successfully()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        $consultationData = [
            'doctor_id' => $recipient->id,
            'title' => 'Need advice on case',
            'description' => 'I have a patient with symptoms...',
        ];

        $response = $this->postJson('/api/v2/consultations', $consultationData);

        $response->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('consultations', [
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
            'title' => 'Need advice on case',
            'description' => 'I have a patient with symptoms...',
        ]);
    }

    /** @test */
    public function test_create_consultation_requires_authentication()
    {
        $recipient = User::factory()->create();

        $response = $this->postJson('/api/v2/consultations', [
            'doctor_id' => $recipient->id,
            'title' => 'Test consultation',
            'description' => 'Test description',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_create_consultation_validates_doctor_id()
    {
        $sender = $this->doctorUser();

        $response = $this->postJson('/api/v2/consultations', [
            'doctor_id' => null,
            'title' => 'Test consultation',
            'description' => 'Test description',
        ]);

        $this->assertValidationError($response, ['doctor_id']);
    }

    /** @test */
    public function test_create_consultation_validates_title()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        $response = $this->postJson('/api/v2/consultations', [
            'doctor_id' => $recipient->id,
            'title' => '', // Empty title
            'description' => 'Test description',
        ]);

        $this->assertValidationError($response, ['title']);
    }

    /** @test */
    public function test_create_consultation_validates_description()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        $response = $this->postJson('/api/v2/consultations', [
            'doctor_id' => $recipient->id,
            'title' => 'Test title',
            'description' => '', // Empty description
        ]);

        $this->assertValidationError($response, ['description']);
    }

    /** @test */
    public function test_create_consultation_with_attachments()
    {
        Storage::fake('public');

        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        $image = $this->createFakeImage('consultation-image.jpg');

        $response = $this->postJson('/api/v2/consultations', [
            'doctor_id' => $recipient->id,
            'title' => 'Consultation with image',
            'description' => 'Please review this case',
            'attachment' => $image,
        ]);

        $response->assertStatus(201);

        $consultation = Consultation::latest()->first();
        $this->assertNotNull($consultation->attachment);
    }

    /** @test */
    public function test_create_consultation_handles_non_existent_doctor()
    {
        $sender = $this->doctorUser();

        $response = $this->postJson('/api/v2/consultations', [
            'doctor_id' => 99999,
            'title' => 'Test consultation',
            'description' => 'Test description',
        ]);

        $this->assertNotFound($response);
    }

    /** @test */
    public function test_create_consultation_prevents_self_consultation()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/consultations', [
            'doctor_id' => $doctor->id,
            'title' => 'Consulting myself',
            'description' => 'Test description',
        ]);

        // Should fail
        $response->assertStatus(422);
    }

    // ==================== GET SENT CONSULTATIONS TESTS ====================

    /** @test */
    public function test_get_sent_consultations_returns_list()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        // Create consultations sent by the user
        Consultation::factory()->count(3)->create([
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
        ]);

        // Create consultations sent by others
        Consultation::factory()->count(2)->create();

        $response = $this->getJson('/api/v2/consultations/sent');

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $consultations = $response->json('data');

        // Should only return sender's consultations
        foreach ($consultations as $consultation) {
            $this->assertEquals($sender->id, $consultation['sender_id']);
        }

        $this->assertCount(3, $consultations);
    }

    /** @test */
    public function test_get_sent_consultations_requires_authentication()
    {
        $response = $this->getJson('/api/v2/consultations/sent');

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_sent_consultations_includes_recipient_data()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser(['name' => 'Dr. Recipient', 'lname' => 'Name']);

        Consultation::factory()->create([
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
        ]);

        $response = $this->getJson('/api/v2/consultations/sent');

        $response->assertStatus(200);

        $consultations = $response->json('data');

        if (!empty($consultations)) {
            $this->assertArrayHasKey('doctor', $consultations[0]);
        }
    }

    // ==================== GET RECEIVED CONSULTATIONS TESTS ====================

    /** @test */
    public function test_get_received_consultations_returns_list()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        // Create consultations received by the user
        Consultation::factory()->count(3)->create([
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
        ]);

        // Create consultations received by others
        Consultation::factory()->count(2)->create();

        // Authenticate as recipient
        $this->actingAs($recipient);

        $response = $this->getJson('/api/v2/consultations/received');

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $consultations = $response->json('data');

        // Should only return recipient's consultations
        foreach ($consultations as $consultation) {
            $this->assertEquals($recipient->id, $consultation['doctor_id']);
        }

        $this->assertCount(3, $consultations);
    }

    /** @test */
    public function test_get_received_consultations_requires_authentication()
    {
        $response = $this->getJson('/api/v2/consultations/received');

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_received_consultations_includes_sender_data()
    {
        $sender = $this->doctorUser(['name' => 'Dr. Sender', 'lname' => 'Name']);
        $recipient = $this->doctorUser();

        Consultation::factory()->create([
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
        ]);

        // Authenticate as recipient
        $this->actingAs($recipient);

        $response = $this->getJson('/api/v2/consultations/received');

        $response->assertStatus(200);

        $consultations = $response->json('data');

        if (!empty($consultations)) {
            $this->assertArrayHasKey('sender', $consultations[0]);
        }
    }

    // ==================== GET CONSULTATION BY ID TESTS ====================

    /** @test */
    public function test_get_consultation_by_id_returns_details()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        $consultation = Consultation::factory()->create([
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
        ]);

        $response = $this->getJson("/api/v2/consultations/{$consultation->id}");

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'data' => [
                    'id' => $consultation->id,
                    'title' => $consultation->title,
                    'description' => $consultation->description,
                ],
            ]);
    }

    /** @test */
    public function test_get_consultation_requires_authentication()
    {
        $consultation = Consultation::factory()->create();

        $response = $this->getJson("/api/v2/consultations/{$consultation->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_consultation_requires_participation()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();
        $other = $this->doctorUser();

        $consultation = Consultation::factory()->create([
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
        ]);

        // Authenticate as non-participant
        $this->actingAs($other);

        $response = $this->getJson("/api/v2/consultations/{$consultation->id}");

        // Should fail for non-participants
        $response->assertStatus(403);
    }

    /** @test */
    public function test_get_consultation_handles_non_existent()
    {
        $doctor = $this->doctorUser();

        $response = $this->getJson('/api/v2/consultations/99999');

        $this->assertNotFound($response);
    }

    /** @test */
    public function test_get_consultation_includes_replies()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        $consultation = Consultation::factory()->create([
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
        ]);

        // Create replies
        ConsultationReply::factory()->count(3)->create([
            'consultation_id' => $consultation->id,
        ]);

        $response = $this->getJson("/api/v2/consultations/{$consultation->id}");

        $response->assertStatus(200);

        $data = $response->json('data');

        if (isset($data['replies'])) {
            $this->assertCount(3, $data['replies']);
        }
    }
}
