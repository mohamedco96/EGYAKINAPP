<?php

namespace Tests\Feature\Api\V3\Chat;

use App\Models\User;
use App\Modules\DirectChat\Models\Conversation;
use App\Modules\DirectChat\Models\ConversationParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Tests for Direct Chat conversation endpoints:
 *
 * GET    /api/v3/chat/conversations
 * POST   /api/v3/chat/conversations
 * GET    /api/v3/chat/conversations/{id}
 * PUT    /api/v3/chat/conversations/{id}
 */
class ConversationCrudTest extends TestCase
{
    use ApiTestHelpers, ChatTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'doctor']);
        Role::firstOrCreate(['name' => 'user']);
    }

    // =========================================================================
    // GET /api/v3/chat/conversations
    // =========================================================================

    /** @test */
    public function test_list_conversations_returns_only_users_own_conversations(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();

        $mine = $this->makePrivateConversation($user, $other);

        // A conversation the user is NOT part of
        $notMine = Conversation::factory()->socialGroup()->create(['created_by' => $other->id]);
        ConversationParticipant::factory()->admin()->create([
            'conversation_id' => $notMine->id,
            'user_id' => $other->id,
        ]);

        $this->actingAs($user);

        $response = $this->getJson('/api/v3/chat/conversations');

        $response->assertSuccessful()
            ->assertJson(['value' => true])
            ->assertJsonStructure(['value', 'message', 'data' => ['counts', 'data']]);

        $ids = collect($response->json('data.data'))->pluck('id');
        expect($ids)->toContain($mine->id)
            ->not->toContain($notMine->id);
    }

    /** @test */
    public function test_list_conversations_includes_counts_by_type(): void
    {
        $user = $this->chatNormalUser();
        $other = User::factory()->create(['email_verified_at' => now()]);

        $this->makePrivateConversation($user, $other);
        $this->makeGroupConversation($user, 'social_group', 'My Group');

        // Re-auth as $user (makePrivateConversation/makeGroupConversation don't change auth)
        $this->actingAs($user);

        $response = $this->getJson('/api/v3/chat/conversations')->assertSuccessful();

        $counts = $response->json('data.counts');
        expect($counts)->toHaveKeys(['all', 'private', 'case_group', 'social_group']);
        expect($counts['all'])->toBeGreaterThanOrEqual(2);
    }

    /** @test */
    public function test_list_conversations_filters_by_type(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();

        $this->makePrivateConversation($user, $other);
        $this->makeGroupConversation($user, 'social_group', 'My Group');

        $this->actingAs($user);
        $response = $this->getJson('/api/v3/chat/conversations?type=social_group')->assertSuccessful();

        foreach ($response->json('data.data') as $conv) {
            expect($conv['type'])->toBe('social_group');
        }
    }

    /** @test */
    public function test_list_conversations_requires_authentication(): void
    {
        $this->getJson('/api/v3/chat/conversations')->assertUnauthorized();
    }

    // =========================================================================
    // POST /api/v3/chat/conversations
    // =========================================================================

    /** @test */
    public function test_create_private_conversation(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();

        $this->actingAs($user);

        $this->postJson('/api/v3/chat/conversations', [
            'type' => 'private',
            'participant_ids' => [$other->id],
        ])
            ->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('conversations', ['type' => 'private', 'created_by' => $user->id]);
        $this->assertDatabaseHas('conversation_participants', ['user_id' => $other->id]);
    }

    /** @test */
    public function test_create_conversation_returns_existing_private_conversation(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();

        $existing = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->postJson('/api/v3/chat/conversations', [
            'type' => 'private',
            'participant_ids' => [$other->id],
        ])
            ->assertSuccessful()
            ->assertJson(['value' => true, 'data' => ['id' => $existing->id]]);
    }

    /** @test */
    public function test_create_social_group_conversation(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();

        $this->actingAs($user);

        $this->postJson('/api/v3/chat/conversations', [
            'type' => 'social_group',
            'name' => 'My Social Group',
            'description' => 'A fun group',
            'participant_ids' => [$other->id],
        ])
            ->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('conversations', ['type' => 'social_group', 'name' => 'My Social Group']);
    }

    /** @test */
    public function test_create_case_group_by_doctor(): void
    {
        $doctor = $this->chatDoctorUser();
        $other = $this->chatDoctorUser();

        $this->actingAs($doctor);

        $this->postJson('/api/v3/chat/conversations', [
            'type' => 'case_group',
            'name' => 'Case Group Alpha',
            'participant_ids' => [$other->id],
        ])
            ->assertStatus(201)
            ->assertJson(['value' => true]);
    }

    /** @test */
    public function test_non_doctor_cannot_create_case_group(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();

        $this->postJson('/api/v3/chat/conversations', [
            'type' => 'case_group',
            'name' => 'Illegal Case Group',
            'participant_ids' => [$other->id],
        ])->assertForbidden();
    }

    /** @test */
    public function test_case_group_rejects_non_doctor_participants(): void
    {
        $doctor = $this->chatDoctorUser();
        $normal = $this->chatNormalUser();

        $this->actingAs($doctor);

        $this->postJson('/api/v3/chat/conversations', [
            'type' => 'case_group',
            'name' => 'Case Group',
            'participant_ids' => [$normal->id],
        ])->assertStatus(422);
    }

    /** @test */
    public function test_private_conversation_requires_exactly_one_other_participant(): void
    {
        $user = $this->chatNormalUser();
        $a = $this->chatNormalUser();
        $b = $this->chatNormalUser();

        $this->actingAs($user);

        $this->postJson('/api/v3/chat/conversations', [
            'type' => 'private',
            'participant_ids' => [$a->id, $b->id],
        ])->assertStatus(422);
    }

    /** @test */
    public function test_group_conversation_requires_name(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();

        $this->actingAs($user);

        $this->postJson('/api/v3/chat/conversations', [
            'type' => 'social_group',
            'participant_ids' => [$other->id],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * @test
     *
     * @dataProvider invalidConversationPayloads
     */
    public function test_create_conversation_validates_required_fields(array $payload, string $field): void
    {
        $this->chatNormalUser();

        $this->postJson('/api/v3/chat/conversations', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors([$field]);
    }

    public static function invalidConversationPayloads(): array
    {
        return [
            'missing type' => [['participant_ids' => [1]], 'type'],
            'invalid type' => [['type' => 'unknown', 'participant_ids' => [1]], 'type'],
            'missing participants' => [['type' => 'private'], 'participant_ids'],
        ];
    }

    /** @test */
    public function test_create_conversation_requires_authentication(): void
    {
        $this->postJson('/api/v3/chat/conversations', [
            'type' => 'private',
            'participant_ids' => [1],
        ])->assertUnauthorized();
    }

    // =========================================================================
    // GET /api/v3/chat/conversations/{id}
    // =========================================================================

    /** @test */
    public function test_show_conversation_returns_details_for_participant(): void
    {
        $user = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($user, 'social_group', 'My Group');

        $this->getJson("/api/v3/chat/conversations/{$conversation->id}")
            ->assertSuccessful()
            ->assertJson(['value' => true, 'data' => ['id' => $conversation->id]]);
    }

    /** @test */
    public function test_show_conversation_returns_403_for_non_participant(): void
    {
        $owner = $this->chatNormalUser();
        $outsider = $this->chatNormalUser();

        $conversation = $this->makeGroupConversation($owner, 'social_group', 'Secret Group');

        $this->actingAs($outsider);

        $this->getJson("/api/v3/chat/conversations/{$conversation->id}")
            ->assertForbidden();
    }

    /** @test */
    public function test_show_conversation_returns_404_for_non_existent(): void
    {
        $this->chatNormalUser();

        $this->getJson('/api/v3/chat/conversations/99999')
            ->assertNotFound();
    }

    /** @test */
    public function test_show_conversation_requires_authentication(): void
    {
        $this->getJson('/api/v3/chat/conversations/1')->assertUnauthorized();
    }

    // =========================================================================
    // PUT /api/v3/chat/conversations/{id}
    // =========================================================================

    /** @test */
    public function test_admin_can_update_group_conversation_name(): void
    {
        $admin = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Old Name');

        $this->putJson("/api/v3/chat/conversations/{$conversation->id}", ['name' => 'New Name'])
            ->assertSuccessful()
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('conversations', ['id' => $conversation->id, 'name' => 'New Name']);
    }

    /** @test */
    public function test_member_cannot_update_group_conversation(): void
    {
        $admin = $this->chatNormalUser();
        $member = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Group');

        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($member);

        $this->putJson("/api/v3/chat/conversations/{$conversation->id}", ['name' => 'Hijacked'])
            ->assertForbidden();
    }

    /** @test */
    public function test_private_conversation_cannot_be_updated(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->putJson("/api/v3/chat/conversations/{$conversation->id}", ['name' => 'New Name'])
            ->assertStatus(422);
    }

    /** @test */
    public function test_update_conversation_returns_404_for_non_existent(): void
    {
        $this->chatNormalUser();

        $this->putJson('/api/v3/chat/conversations/99999', ['name' => 'Ghost'])
            ->assertNotFound();
    }

    /** @test */
    public function test_update_conversation_requires_authentication(): void
    {
        $this->putJson('/api/v3/chat/conversations/1', ['name' => 'Test'])
            ->assertUnauthorized();
    }

    // =========================================================================
    // Edge cases — GET /api/v3/chat/conversations
    // =========================================================================

    /** @test */
    public function test_list_conversations_returns_empty_when_user_has_none(): void
    {
        $this->chatNormalUser();

        $response = $this->getJson('/api/v3/chat/conversations')->assertSuccessful();

        expect($response->json('data.data'))->toBeEmpty();
        expect($response->json('data.counts.all'))->toBe(0);
    }

    // =========================================================================
    // Edge cases — POST /api/v3/chat/conversations
    // =========================================================================

    /** @test */
    public function test_create_conversation_creator_is_added_as_admin(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();

        $this->actingAs($user);

        $this->postJson('/api/v3/chat/conversations', [
            'type' => 'social_group',
            'name' => 'Test Group',
            'participant_ids' => [$other->id],
        ])->assertStatus(201);

        $this->assertDatabaseHas('conversation_participants', [
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function test_create_social_group_adds_participants_as_members_with_mute_on(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();

        $this->actingAs($user);

        $this->postJson('/api/v3/chat/conversations', [
            'type' => 'social_group',
            'name' => 'Social Test',
            'participant_ids' => [$other->id],
        ])->assertStatus(201);

        $this->assertDatabaseHas('conversation_participants', [
            'user_id' => $other->id,
            'role' => 'member',
            'mute_notifications' => true,
        ]);
    }

    /** @test */
    public function test_create_conversation_filters_out_creator_from_participant_ids(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();

        $this->actingAs($user);

        // Including creator's own ID in participant_ids should not create a duplicate participant
        $this->postJson('/api/v3/chat/conversations', [
            'type' => 'social_group',
            'name' => 'Creator in List',
            'participant_ids' => [$user->id, $other->id],
        ])->assertStatus(201);

        // Creator appears only once (as admin)
        $this->assertDatabaseCount('conversation_participants', 2);
    }

    // =========================================================================
    // Edge cases — PUT /api/v3/chat/conversations/{id}
    // =========================================================================

    /** @test */
    public function test_update_conversation_description(): void
    {
        $admin = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Group');

        $this->putJson("/api/v3/chat/conversations/{$conversation->id}", [
            'description' => 'Updated description',
        ])
            ->assertSuccessful()
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'description' => 'Updated description',
        ]);
    }

    /** @test */
    public function test_update_conversation_with_image(): void
    {
        $admin = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Group');

        $image = $this->createFakeImage('group.jpg');

        $this->putJson("/api/v3/chat/conversations/{$conversation->id}", [
            'image' => $image,
        ])
            ->assertSuccessful()
            ->assertJson(['value' => true]);

        $updated = $conversation->fresh();
        $this->assertNotNull($updated->image);
    }
}
