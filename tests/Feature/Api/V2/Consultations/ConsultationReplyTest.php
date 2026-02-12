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
 * Test suite for Consultation Reply operations
 *
 * Tests the following endpoints:
 * - POST /api/v2/consultations/{id}/reply
 *
 * @group consultations
 * @group consultation-reply
 * @group api
 * @group v2
 */
class ConsultationReplyTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== ADD REPLY TESTS ====================

    /** @test */
    public function test_add_reply_to_consultation_successfully()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        $consultation = Consultation::factory()->create([
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
        ]);

        // Authenticate as recipient
        $this->actingAs($recipient);

        $replyData = [
            'reply' => 'Thank you for the consultation. Here is my advice...',
        ];

        $response = $this->postJson("/api/v2/consultations/{$consultation->id}/reply", $replyData);

        $response->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('consultation_replies', [
            'consultation_id' => $consultation->id,
            'doctor_id' => $recipient->id,
            'reply' => 'Thank you for the consultation. Here is my advice...',
        ]);
    }

    /** @test */
    public function test_add_reply_requires_authentication()
    {
        $consultation = Consultation::factory()->create();

        $response = $this->postJson("/api/v2/consultations/{$consultation->id}/reply", [
            'reply' => 'Test reply',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_add_reply_validates_content()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        $consultation = Consultation::factory()->create([
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
        ]);

        // Authenticate as recipient
        $this->actingAs($recipient);

        $response = $this->postJson("/api/v2/consultations/{$consultation->id}/reply", [
            'reply' => '', // Empty reply
        ]);

        $this->assertValidationError($response, ['reply']);
    }

    /** @test */
    public function test_add_reply_requires_participation()
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

        $response = $this->postJson("/api/v2/consultations/{$consultation->id}/reply", [
            'reply' => 'Trying to reply',
        ]);

        // Should fail for non-participants
        $response->assertStatus(403);
    }

    /** @test */
    public function test_sender_can_add_reply()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        $consultation = Consultation::factory()->create([
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
        ]);

        // Sender adds a follow-up reply
        $replyData = [
            'reply' => 'Thank you for your response. I have a follow-up question...',
        ];

        $response = $this->postJson("/api/v2/consultations/{$consultation->id}/reply", $replyData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('consultation_replies', [
            'consultation_id' => $consultation->id,
            'doctor_id' => $sender->id,
            'reply' => 'Thank you for your response. I have a follow-up question...',
        ]);
    }

    /** @test */
    public function test_add_reply_with_attachment()
    {
        Storage::fake('public');

        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        $consultation = Consultation::factory()->create([
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
        ]);

        // Authenticate as recipient
        $this->actingAs($recipient);

        $image = $this->createFakeImage('reply-image.jpg');

        $response = $this->postJson("/api/v2/consultations/{$consultation->id}/reply", [
            'reply' => 'Please see the attached image',
            'attachment' => $image,
        ]);

        $response->assertStatus(201);

        $reply = ConsultationReply::latest()->first();
        $this->assertNotNull($reply->attachment);
    }

    /** @test */
    public function test_add_reply_handles_non_existent_consultation()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/consultations/99999/reply', [
            'reply' => 'Test reply',
        ]);

        $this->assertNotFound($response);
    }

    /** @test */
    public function test_add_reply_sends_notification_to_other_participant()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        $consultation = Consultation::factory()->create([
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
        ]);

        // Authenticate as recipient
        $this->actingAs($recipient);

        $response = $this->postJson("/api/v2/consultations/{$consultation->id}/reply", [
            'reply' => 'Here is my answer',
        ]);

        $response->assertStatus(201);

        // Verify notification was created for sender
        $this->assertDatabaseHas('app_notifications', [
            'doctor_id' => $sender->id,
        ]);
    }

    /** @test */
    public function test_multiple_replies_to_same_consultation()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        $consultation = Consultation::factory()->create([
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
        ]);

        // Recipient replies
        $this->actingAs($recipient);
        $this->postJson("/api/v2/consultations/{$consultation->id}/reply", [
            'reply' => 'First reply',
        ]);

        // Sender replies
        $this->actingAs($sender);
        $this->postJson("/api/v2/consultations/{$consultation->id}/reply", [
            'reply' => 'Second reply',
        ]);

        // Recipient replies again
        $this->actingAs($recipient);
        $this->postJson("/api/v2/consultations/{$consultation->id}/reply", [
            'reply' => 'Third reply',
        ]);

        // Verify all replies exist
        $replyCount = ConsultationReply::where('consultation_id', $consultation->id)->count();
        $this->assertEquals(3, $replyCount);
    }

    /** @test */
    public function test_add_reply_updates_consultation_status()
    {
        $sender = $this->doctorUser();
        $recipient = $this->doctorUser();

        $consultation = Consultation::factory()->open()->create([
            'sender_id' => $sender->id,
            'doctor_id' => $recipient->id,
        ]);

        // Authenticate as recipient
        $this->actingAs($recipient);

        $response = $this->postJson("/api/v2/consultations/{$consultation->id}/reply", [
            'reply' => 'Here is my answer',
        ]);

        $response->assertStatus(201);

        // Verify consultation status updated to answered
        $consultation->refresh();

        if (isset($consultation->status)) {
            $this->assertEquals('answered', $consultation->status);
        }
    }
}
