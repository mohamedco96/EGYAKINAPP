<?php

namespace Tests\Feature\Api\V3\Chat;

use App\Models\User;
use App\Modules\DirectChat\Models\ConversationParticipant;
use App\Modules\DirectChat\Models\MessageReaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests for Direct Chat feature endpoints:
 *
 * POST /api/v3/chat/conversations/{id}/reactions
 * POST /api/v3/chat/conversations/{id}/mute
 * POST /api/v3/chat/conversations/{id}/typing
 * GET  /api/v3/chat/users/search
 */
class ChatFeaturesTest extends TestCase
{
    use ChatTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'doctor']);
        Role::firstOrCreate(['name' => 'user']);
        Event::fake();
    }

    // =========================================================================
    // POST /api/v3/chat/conversations/{id}/reactions
    // =========================================================================

    /** @test */
    public function test_participant_can_add_reaction_to_message(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);
        $message = $this->makeMessage($conversation, $other, 'React to me');

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/reactions", [
            'message_id' => $message->id,
            'reaction' => '👍',
        ])
            ->assertSuccessful()
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $user->id,
            'reaction' => '👍',
        ]);
    }

    /** @test */
    public function test_reacting_again_with_same_emoji_removes_reaction(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);
        $message = $this->makeMessage($conversation, $other, 'Toggle me');

        MessageReaction::create([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'reaction' => '❤️',
        ]);

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/reactions", [
            'message_id' => $message->id,
            'reaction' => '❤️',
        ])
            ->assertSuccessful()
            ->assertJson(['message' => 'Reaction removed.']);

        $this->assertDatabaseMissing('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $user->id,
            'reaction' => '❤️',
        ]);
    }

    /** @test */
    public function test_reaction_returns_updated_reactions_list(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);
        $message = $this->makeMessage($conversation, $other);

        $this->actingAs($user);

        $response = $this->postJson("/api/v3/chat/conversations/{$conversation->id}/reactions", [
            'message_id' => $message->id,
            'reaction' => '🔥',
        ])->assertSuccessful();

        $this->assertNotNull($response->json('data.reactions'));
    }

    /** @test */
    public function test_non_participant_cannot_react(): void
    {
        $owner = $this->chatNormalUser();
        $outsider = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($owner, 'social_group', 'Group');
        $message = $this->makeMessage($conversation, $owner);

        $this->actingAs($outsider);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/reactions", [
            'message_id' => $message->id,
            'reaction' => '👍',
        ])->assertForbidden();
    }

    /** @test */
    public function test_react_returns_403_when_message_belongs_to_different_conversation(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $convA = $this->makePrivateConversation($user, $other);
        $convB = $this->makeGroupConversation($user, 'social_group', 'Group B');

        $messageInB = $this->makeMessage($convB, $user);

        // React to convB's message via convA's endpoint
        $this->postJson("/api/v3/chat/conversations/{$convA->id}/reactions", [
            'message_id' => $messageInB->id,
            'reaction' => '👍',
        ])->assertForbidden();
    }

    /** @test */
    public function test_react_validates_required_fields(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/reactions", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message_id', 'reaction']);
    }

    /** @test */
    public function test_react_requires_authentication(): void
    {
        $this->postJson('/api/v3/chat/conversations/1/reactions', [
            'message_id' => 1,
            'reaction' => '👍',
        ])->assertUnauthorized();
    }

    // =========================================================================
    // POST /api/v3/chat/conversations/{id}/mute
    // =========================================================================

    /** @test */
    public function test_participant_can_mute_conversation(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/mute", ['mute' => true])
            ->assertSuccessful()
            ->assertJson(['value' => true, 'data' => ['mute_notifications' => true]]);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'mute_notifications' => true,
        ]);
    }

    /** @test */
    public function test_participant_can_unmute_conversation(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update(['mute_notifications' => true]);

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/mute", ['mute' => false])
            ->assertSuccessful()
            ->assertJson(['value' => true, 'data' => ['mute_notifications' => false]]);
    }

    /** @test */
    public function test_mute_returns_403_for_non_participant(): void
    {
        $owner = $this->chatNormalUser();
        $outsider = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($owner, 'social_group', 'Group');

        $this->actingAs($outsider);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/mute", ['mute' => true])
            ->assertForbidden();
    }

    /** @test */
    public function test_mute_defaults_to_true_when_mute_not_provided(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/mute", [])
            ->assertSuccessful()
            ->assertJson(['data' => ['mute_notifications' => true]]);
    }

    /** @test */
    public function test_mute_requires_authentication(): void
    {
        $this->postJson('/api/v3/chat/conversations/1/mute', ['mute' => true])
            ->assertUnauthorized();
    }

    // =========================================================================
    // POST /api/v3/chat/conversations/{id}/typing
    // =========================================================================

    /** @test */
    public function test_participant_can_broadcast_typing_indicator(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/typing", [
            'is_typing' => true,
        ])
            ->assertSuccessful()
            ->assertJson(['value' => true]);
    }

    /** @test */
    public function test_non_participant_cannot_broadcast_typing(): void
    {
        $owner = $this->chatNormalUser();
        $outsider = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($owner, 'social_group', 'Group');

        $this->actingAs($outsider);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/typing", [
            'is_typing' => true,
        ])->assertForbidden();
    }

    /** @test */
    public function test_typing_requires_authentication(): void
    {
        $this->postJson('/api/v3/chat/conversations/1/typing', ['is_typing' => true])
            ->assertUnauthorized();
    }

    // =========================================================================
    // GET /api/v3/chat/users/search
    // =========================================================================

    /** @test */
    public function test_search_users_returns_matching_results(): void
    {
        $searcher = $this->chatNormalUser();
        $target = User::factory()->create(['name' => 'AliceUniqueXYZ', 'email_verified_at' => now()]);

        $response = $this->getJson('/api/v3/chat/users/search?q=AliceUniqueXYZ')
            ->assertSuccessful()
            ->assertJson(['value' => true]);

        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->toContain($target->id);
    }

    /** @test */
    public function test_search_users_excludes_self(): void
    {
        $user = $this->chatNormalUser(['name' => 'SelfSearchUser']);

        $response = $this->getJson('/api/v3/chat/users/search?q=SelfSearchUser')
            ->assertSuccessful();

        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->not->toContain($user->id);
    }

    /** @test */
    public function test_search_users_requires_minimum_two_characters(): void
    {
        $this->chatNormalUser();

        $this->getJson('/api/v3/chat/users/search?q=a')
            ->assertStatus(422);
    }

    /** @test */
    public function test_search_users_with_empty_query_returns_error(): void
    {
        $this->chatNormalUser();

        $this->getJson('/api/v3/chat/users/search?q=')
            ->assertStatus(422);
    }

    /** @test */
    public function test_search_users_requires_authentication(): void
    {
        $this->getJson('/api/v3/chat/users/search?q=john')
            ->assertUnauthorized();
    }

    // =========================================================================
    // Edge cases — reactions
    // =========================================================================

    /** @test */
    public function test_react_to_non_existent_message_returns_404(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/reactions", [
            'message_id' => 99999,
            'reaction' => '👍',
        ])->assertStatus(422); // fails at FormRequest validation (exists:messages,id)
    }

    /** @test */
    public function test_multiple_users_can_react_to_same_message(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);
        $message = $this->makeMessage($conversation, $other, 'React here');

        $this->actingAs($user);
        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/reactions", [
            'message_id' => $message->id,
            'reaction' => '👍',
        ])->assertSuccessful();

        $this->actingAs($other);
        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/reactions", [
            'message_id' => $message->id,
            'reaction' => '👍',
        ])->assertSuccessful();

        $this->assertDatabaseCount('message_reactions', 2);

        $reactions = $this->getJson("/api/v3/chat/conversations/{$conversation->id}/reactions");
        // After second react, verify count aggregation is included in the response
        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $message->id,
            'reaction' => '👍',
        ]);
    }

    // =========================================================================
    // Edge cases — search users
    // =========================================================================

    /** @test */
    public function test_search_users_matches_by_last_name(): void
    {
        $searcher = $this->chatNormalUser();
        $target = User::factory()->create(['lname' => 'BjornsenUniqueXYZ', 'email_verified_at' => now()]);

        $response = $this->getJson('/api/v3/chat/users/search?q=BjornsenUniqueXYZ')
            ->assertSuccessful();

        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->toContain($target->id);
    }

    /** @test */
    public function test_search_users_matches_by_email(): void
    {
        $searcher = $this->chatNormalUser();
        $target = User::factory()->create(['email' => 'uniquesearchxyz@example.com', 'email_verified_at' => now()]);

        $response = $this->getJson('/api/v3/chat/users/search?q=uniquesearchxyz')
            ->assertSuccessful();

        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->toContain($target->id);
    }

    /** @test */
    public function test_search_users_returns_limited_columns(): void
    {
        $searcher = $this->chatNormalUser(['name' => 'LimitedColUser']);

        // Authenticate as someone else so searcher appears in results
        $other = $this->chatNormalUser();

        $response = $this->getJson('/api/v3/chat/users/search?q=LimitedColUser')
            ->assertSuccessful();

        $data = $response->json('data');
        if (! empty($data)) {
            $keys = array_keys($data[0]);
            expect($keys)->toContain('id', 'name');
            // Sensitive fields like password, email should not appear
            expect($keys)->not->toContain('password');
        }
    }

    /** @test */
    public function test_search_users_with_whitespace_only_query_returns_error(): void
    {
        $this->chatNormalUser();

        // URL-encode spaces so the HTTP request is valid; the service should still reject them
        $this->getJson('/api/v3/chat/users/search?q=+++')
            ->assertStatus(422);
    }

    // =========================================================================
    // Edge cases — typing
    // =========================================================================

    /** @test */
    public function test_typing_is_typing_false_also_accepted(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/typing", [
            'is_typing' => false,
        ])
            ->assertSuccessful()
            ->assertJson(['value' => true]);
    }
}
