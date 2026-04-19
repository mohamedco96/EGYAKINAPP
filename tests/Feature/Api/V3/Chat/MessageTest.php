<?php

namespace Tests\Feature\Api\V3\Chat;

use App\Modules\DirectChat\Models\Conversation;
use App\Modules\DirectChat\Models\ConversationParticipant;
use App\Modules\DirectChat\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Tests for Direct Chat message endpoints:
 *
 * GET    /api/v3/chat/conversations/{id}/messages
 * POST   /api/v3/chat/conversations/{id}/messages
 * DELETE /api/v3/chat/conversations/{id}/messages/{messageId}
 * POST   /api/v3/chat/direct/{userId}
 */
class MessageTest extends TestCase
{
    use ApiTestHelpers, ChatTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'doctor']);
        Role::firstOrCreate(['name' => 'user']);
        Event::fake(); // prevent real broadcast attempts
    }

    // =========================================================================
    // GET /api/v3/chat/conversations/{id}/messages
    // =========================================================================

    /** @test */
    public function test_get_messages_returns_paginated_messages_for_participant(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->makeMessage($conversation, $other, 'First message');
        $this->makeMessage($conversation, $user, 'Second message');

        $this->actingAs($user);

        $response = $this->getJson("/api/v3/chat/conversations/{$conversation->id}/messages");

        $response->assertSuccessful()
            ->assertJson(['value' => true])
            ->assertJsonStructure(['value', 'message', 'data', 'has_more']);

        expect($response->json('data'))->toHaveCount(2);
    }

    /** @test */
    public function test_get_messages_supports_cursor_pagination_with_before(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $messages = Message::factory()->count(5)->text()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $other->id,
        ]);

        $pivot = $messages->get(2)->id; // fetch messages before this one

        $this->actingAs($user);

        $response = $this->getJson("/api/v3/chat/conversations/{$conversation->id}/messages?before={$pivot}")
            ->assertSuccessful();

        foreach ($response->json('data') as $msg) {
            expect($msg['id'])->toBeLessThan($pivot);
        }
    }

    /** @test */
    public function test_get_messages_returns_403_for_non_participant(): void
    {
        $owner = $this->chatNormalUser();
        $outsider = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($owner, 'social_group', 'Group');

        $this->actingAs($outsider);

        $this->getJson("/api/v3/chat/conversations/{$conversation->id}/messages")
            ->assertForbidden();
    }

    /** @test */
    public function test_get_messages_requires_authentication(): void
    {
        $this->getJson('/api/v3/chat/conversations/1/messages')->assertUnauthorized();
    }

    // =========================================================================
    // POST /api/v3/chat/conversations/{id}/messages
    // =========================================================================

    /** @test */
    public function test_send_text_message_to_conversation(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $response = $this->postJson("/api/v3/chat/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'content' => 'Hello there!',
        ]);

        $response->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'type' => 'text',
            'content' => 'Hello there!',
        ]);
    }

    /** @test */
    public function test_send_message_returns_403_for_non_participant(): void
    {
        $owner = $this->chatNormalUser();
        $outsider = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($owner, 'social_group', 'Group');

        $this->actingAs($outsider);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'content' => 'Sneaky message',
        ])->assertForbidden();
    }

    /** @test */
    public function test_non_doctor_cannot_send_message_in_case_group(): void
    {
        $doctor = $this->chatDoctorUser();
        $normal = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($doctor, 'case_group', 'Case Group');

        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $normal->id,
            'role' => 'member',
        ]);

        $this->actingAs($normal);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'content' => 'Not a doctor',
        ])->assertForbidden();
    }

    /** @test */
    public function test_send_message_allows_reply_to_existing_message(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);
        $original = $this->makeMessage($conversation, $other, 'Original');

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'content' => 'Reply!',
            'reply_to_id' => $original->id,
        ])
            ->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('messages', [
            'reply_to_id' => $original->id,
            'content' => 'Reply!',
        ]);
    }

    /** @test */
    public function test_send_message_validates_required_fields(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/messages", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function test_send_message_requires_content_for_text_type(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            // content missing
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    /** @test */
    public function test_send_message_returns_404_for_non_existent_conversation(): void
    {
        $this->chatNormalUser();

        $this->postJson('/api/v3/chat/conversations/99999/messages', [
            'type' => 'text',
            'content' => 'Hello',
        ])->assertNotFound();
    }

    /** @test */
    public function test_send_message_requires_authentication(): void
    {
        $this->postJson('/api/v3/chat/conversations/1/messages', [
            'type' => 'text',
            'content' => 'Hello',
        ])->assertUnauthorized();
    }

    // =========================================================================
    // DELETE /api/v3/chat/conversations/{id}/messages/{messageId}
    // =========================================================================

    /** @test */
    public function test_sender_can_delete_own_message(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);
        $message = $this->makeMessage($conversation, $user, 'Delete me');

        $this->actingAs($user);

        $this->deleteJson("/api/v3/chat/conversations/{$conversation->id}/messages/{$message->id}")
            ->assertSuccessful()
            ->assertJson(['value' => true]);

        $this->assertSoftDeleted('messages', ['id' => $message->id]);
    }

    /** @test */
    public function test_non_sender_cannot_delete_message(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);
        $message = $this->makeMessage($conversation, $user, 'Mine');

        $this->actingAs($other); // other tries to delete user's message

        $this->deleteJson("/api/v3/chat/conversations/{$conversation->id}/messages/{$message->id}")
            ->assertForbidden();
    }

    /** @test */
    public function test_delete_message_returns_404_for_non_existent_message(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->deleteJson("/api/v3/chat/conversations/{$conversation->id}/messages/99999")
            ->assertNotFound();
    }

    /** @test */
    public function test_delete_message_requires_authentication(): void
    {
        $this->deleteJson('/api/v3/chat/conversations/1/messages/1')->assertUnauthorized();
    }

    // =========================================================================
    // POST /api/v3/chat/direct/{userId}
    // =========================================================================

    /** @test */
    public function test_send_direct_message_creates_conversation_and_sends_message(): void
    {
        $sender = $this->chatNormalUser();
        $recipient = $this->chatNormalUser();

        $this->actingAs($sender);

        $response = $this->postJson("/api/v3/chat/direct/{$recipient->id}", [
            'type' => 'text',
            'content' => 'Hey!',
        ]);

        $response->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'type' => 'text',
            'content' => 'Hey!',
        ]);
    }

    /** @test */
    public function test_send_direct_message_reuses_existing_private_conversation(): void
    {
        $sender = $this->chatNormalUser();
        $recipient = $this->chatNormalUser();

        $existing = $this->makePrivateConversation($sender, $recipient);

        $this->actingAs($sender);

        $this->postJson("/api/v3/chat/direct/{$recipient->id}", [
            'type' => 'text',
            'content' => 'Again!',
        ])->assertStatus(201);

        // Should not have created a second conversation
        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $existing->id,
            'content' => 'Again!',
        ]);
    }

    /** @test */
    public function test_send_direct_message_prevents_messaging_yourself(): void
    {
        $user = $this->chatNormalUser();

        $this->postJson("/api/v3/chat/direct/{$user->id}", [
            'type' => 'text',
            'content' => 'Talking to myself',
        ])->assertStatus(422);
    }

    /** @test */
    public function test_send_direct_message_returns_404_for_non_existent_recipient(): void
    {
        $this->chatNormalUser();

        $this->postJson('/api/v3/chat/direct/99999', [
            'type' => 'text',
            'content' => 'Hello ghost',
        ])->assertNotFound();
    }

    /** @test */
    public function test_send_direct_message_requires_authentication(): void
    {
        $this->postJson('/api/v3/chat/direct/1', [
            'type' => 'text',
            'content' => 'Hello',
        ])->assertUnauthorized();
    }

    // =========================================================================
    // Edge cases — GET messages
    // =========================================================================

    /** @test */
    public function test_get_messages_returns_empty_for_conversation_with_no_messages(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $response = $this->getJson("/api/v3/chat/conversations/{$conversation->id}/messages")
            ->assertSuccessful();

        expect($response->json('data'))->toBeEmpty();
        expect($response->json('has_more'))->toBeFalse();
    }

    /** @test */
    public function test_get_messages_excludes_soft_deleted_messages(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $visible = $this->makeMessage($conversation, $other, 'Visible');
        $deleted = $this->makeMessage($conversation, $other, 'Deleted');
        $deleted->delete(); // soft delete

        $this->actingAs($user);

        $response = $this->getJson("/api/v3/chat/conversations/{$conversation->id}/messages")
            ->assertSuccessful();

        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->toContain($visible->id)
            ->not->toContain($deleted->id);
    }

    /** @test */
    public function test_get_messages_has_more_is_true_when_over_30_messages_exist(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        // Create 31 messages — service fetches 31 to detect has_more
        Message::factory()->count(31)->text()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $other->id,
        ]);

        $this->actingAs($user);

        $response = $this->getJson("/api/v3/chat/conversations/{$conversation->id}/messages")
            ->assertSuccessful();

        expect($response->json('has_more'))->toBeTrue();
        expect($response->json('data'))->toHaveCount(30);
    }

    // =========================================================================
    // Edge cases — POST send message
    // =========================================================================

    /** @test */
    public function test_send_invalid_message_type_fails_validation(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/messages", [
            'type' => 'sticker', // invalid type
            'content' => 'Hi',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function test_send_image_message_requires_file(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/messages", [
            'type' => 'image',
            // file missing
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function test_send_image_message_with_valid_file(): void
    {
        Storage::fake('chat_private');
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $image = $this->createFakeImage('photo.jpg');

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/messages", [
            'type' => 'image',
            'file' => $image,
        ])
            ->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'type' => 'image',
        ]);
    }

    /** @test */
    public function test_reply_to_non_existent_message_fails_validation(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'content' => 'Reply to ghost',
            'reply_to_id' => 99999,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reply_to_id']);
    }

    // =========================================================================
    // Edge cases — DELETE message
    // =========================================================================

    /** @test */
    public function test_non_participant_cannot_delete_message_even_if_sender(): void
    {
        $sender = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($sender, $other);

        // Create message while sender is a participant
        $message = $this->makeMessage($conversation, $sender, 'Hi');

        // Remove sender from the conversation
        ConversationParticipant::where('user_id', $sender->id)
            ->where('conversation_id', $conversation->id)
            ->delete();

        $this->actingAs($sender);

        $this->deleteJson("/api/v3/chat/conversations/{$conversation->id}/messages/{$message->id}")
            ->assertForbidden();
    }
}
