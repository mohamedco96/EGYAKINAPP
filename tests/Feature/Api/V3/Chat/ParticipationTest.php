<?php

namespace Tests\Feature\Api\V3\Chat;

use App\Modules\DirectChat\Models\ConversationParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests for Direct Chat participation endpoints:
 *
 * POST   /api/v3/chat/conversations/{id}/join
 * POST   /api/v3/chat/conversations/{id}/leave
 * POST   /api/v3/chat/conversations/{id}/participants
 * DELETE /api/v3/chat/conversations/{id}/participants/{userId}
 */
class ParticipationTest extends TestCase
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
    // POST /api/v3/chat/conversations/{id}/join
    // =========================================================================

    /** @test */
    public function test_user_can_join_social_group(): void
    {
        $owner = $this->chatNormalUser();
        $joiner = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($owner, 'social_group', 'Open Group');

        $this->actingAs($joiner);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/join")
            ->assertSuccessful()
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $joiner->id,
        ]);
    }

    /** @test */
    public function test_user_cannot_join_private_conversation(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $outsider = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($outsider);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/join")
            ->assertForbidden();
    }

    /** @test */
    public function test_user_cannot_join_case_group(): void
    {
        $doctor = $this->chatDoctorUser();
        $outsider = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($doctor, 'case_group', 'Case Group');

        $this->actingAs($outsider);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/join")
            ->assertForbidden();
    }

    /** @test */
    public function test_user_cannot_join_conversation_they_are_already_in(): void
    {
        $user = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($user, 'social_group', 'Group');

        // user is already admin/participant
        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/join")
            ->assertStatus(422);
    }

    /** @test */
    public function test_join_returns_404_for_non_existent_conversation(): void
    {
        $this->chatNormalUser();

        $this->postJson('/api/v3/chat/conversations/99999/join')
            ->assertNotFound();
    }

    /** @test */
    public function test_join_requires_authentication(): void
    {
        $this->postJson('/api/v3/chat/conversations/1/join')->assertUnauthorized();
    }

    // =========================================================================
    // POST /api/v3/chat/conversations/{id}/leave
    // =========================================================================

    /** @test */
    public function test_member_can_leave_social_group(): void
    {
        $owner = $this->chatNormalUser();
        $member = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($owner, 'social_group', 'Group');

        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($member);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/leave")
            ->assertSuccessful()
            ->assertJson(['value' => true]);

        $this->assertDatabaseMissing('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
        ]);
    }

    /** @test */
    public function test_leaving_admin_promotes_next_member_to_admin(): void
    {
        $admin = $this->chatNormalUser();
        $member = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Group');

        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/leave")
            ->assertSuccessful();

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function test_user_cannot_leave_private_conversation(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/leave")
            ->assertStatus(422);
    }

    /** @test */
    public function test_leave_returns_404_for_non_member(): void
    {
        $owner = $this->chatNormalUser();
        $outsider = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($owner, 'social_group', 'Group');

        $this->actingAs($outsider);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/leave")
            ->assertStatus(404);
    }

    /** @test */
    public function test_leave_requires_authentication(): void
    {
        $this->postJson('/api/v3/chat/conversations/1/leave')->assertUnauthorized();
    }

    // =========================================================================
    // POST /api/v3/chat/conversations/{id}/participants
    // =========================================================================

    /** @test */
    public function test_admin_can_add_participants_to_group(): void
    {
        $admin = $this->chatNormalUser();
        $newMember = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Group');

        $this->actingAs($admin);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/participants", [
            'user_ids' => [$newMember->id],
        ])
            ->assertSuccessful()
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $newMember->id,
        ]);
    }

    /** @test */
    public function test_non_admin_cannot_add_participants(): void
    {
        $admin = $this->chatNormalUser();
        $member = $this->chatNormalUser();
        $newMember = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Group');

        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($member);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/participants", [
            'user_ids' => [$newMember->id],
        ])->assertForbidden();
    }

    /** @test */
    public function test_cannot_add_participants_to_private_conversation(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $third = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $this->actingAs($user);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/participants", [
            'user_ids' => [$third->id],
        ])->assertStatus(422);
    }

    /** @test */
    public function test_case_group_only_allows_doctor_participants(): void
    {
        $doctor = $this->chatDoctorUser();
        $normal = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($doctor, 'case_group', 'Case Group');

        $this->actingAs($doctor);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/participants", [
            'user_ids' => [$normal->id],
        ])->assertStatus(422);
    }

    /** @test */
    public function test_add_participants_skips_already_existing_members(): void
    {
        $admin = $this->chatNormalUser();
        $member = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Group');

        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($admin);

        // Posting the already-existing member again should not error or duplicate
        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/participants", [
            'user_ids' => [$member->id],
        ])->assertSuccessful();

        $this->assertDatabaseCount('conversation_participants', 2); // admin + member, no duplicate
    }

    /** @test */
    public function test_add_participants_validates_user_ids(): void
    {
        $admin = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Group');

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/participants", [
            'user_ids' => [],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_ids']);
    }

    /** @test */
    public function test_add_participants_requires_authentication(): void
    {
        $this->postJson('/api/v3/chat/conversations/1/participants', [
            'user_ids' => [1],
        ])->assertUnauthorized();
    }

    // =========================================================================
    // DELETE /api/v3/chat/conversations/{id}/participants/{userId}
    // =========================================================================

    /** @test */
    public function test_admin_can_remove_participant(): void
    {
        $admin = $this->chatNormalUser();
        $member = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Group');

        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($admin);

        $this->deleteJson("/api/v3/chat/conversations/{$conversation->id}/participants/{$member->id}")
            ->assertSuccessful()
            ->assertJson(['value' => true]);

        $this->assertDatabaseMissing('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
        ]);
    }

    /** @test */
    public function test_non_admin_cannot_remove_participant(): void
    {
        $admin = $this->chatNormalUser();
        $member = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Group');

        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);
        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $other->id,
            'role' => 'member',
        ]);

        $this->actingAs($member);

        $this->deleteJson("/api/v3/chat/conversations/{$conversation->id}/participants/{$other->id}")
            ->assertForbidden();
    }

    /** @test */
    public function test_admin_cannot_remove_themselves_via_remove_endpoint(): void
    {
        $admin = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Group');

        $this->deleteJson("/api/v3/chat/conversations/{$conversation->id}/participants/{$admin->id}")
            ->assertStatus(422);
    }

    /** @test */
    public function test_remove_participant_returns_404_when_user_not_in_conversation(): void
    {
        $admin = $this->chatNormalUser();
        $outsider = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Group');

        $this->actingAs($admin);

        $this->deleteJson("/api/v3/chat/conversations/{$conversation->id}/participants/{$outsider->id}")
            ->assertNotFound();
    }

    /** @test */
    public function test_remove_participant_requires_authentication(): void
    {
        $this->deleteJson('/api/v3/chat/conversations/1/participants/1')->assertUnauthorized();
    }

    // =========================================================================
    // Edge cases — join
    // =========================================================================

    /** @test */
    public function test_joined_member_has_mute_notifications_enabled_by_default(): void
    {
        $owner = $this->chatNormalUser();
        $joiner = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($owner, 'social_group', 'Open Group');

        $this->actingAs($joiner);
        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/join")
            ->assertSuccessful();

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $joiner->id,
            'mute_notifications' => true,
        ]);
    }

    // =========================================================================
    // Edge cases — leave
    // =========================================================================

    /** @test */
    public function test_leave_conversation_returns_404_for_non_existent_conversation(): void
    {
        $this->chatNormalUser();

        $this->postJson('/api/v3/chat/conversations/99999/leave')
            ->assertNotFound();
    }

    /** @test */
    public function test_last_admin_leaving_group_with_no_other_members_removes_cleanly(): void
    {
        $admin = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Solo Group');
        // admin is the only participant

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/leave")
            ->assertSuccessful();

        $this->assertDatabaseMissing('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $admin->id,
        ]);
    }

    // =========================================================================
    // Edge cases — add participants
    // =========================================================================

    /** @test */
    public function test_add_participants_to_social_group_sets_mute_on(): void
    {
        $admin = $this->chatNormalUser();
        $newMember = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Group');

        $this->actingAs($admin);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/participants", [
            'user_ids' => [$newMember->id],
        ])->assertSuccessful();

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $newMember->id,
            'mute_notifications' => true,
        ]);
    }

    /** @test */
    public function test_add_participants_response_contains_added_count(): void
    {
        $admin = $this->chatNormalUser();
        $a = $this->chatNormalUser();
        $b = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($admin, 'social_group', 'Group');

        $this->actingAs($admin);

        $this->postJson("/api/v3/chat/conversations/{$conversation->id}/participants", [
            'user_ids' => [$a->id, $b->id],
        ])
            ->assertSuccessful()
            ->assertJsonFragment(['message' => '2 participant(s) added.']);
    }

    /** @test */
    public function test_add_participants_to_non_existent_conversation_returns_404(): void
    {
        $admin = $this->chatNormalUser();
        $other = $this->chatNormalUser();

        $this->actingAs($admin);

        $this->postJson('/api/v3/chat/conversations/99999/participants', [
            'user_ids' => [$other->id],
        ])->assertNotFound();
    }

    // =========================================================================
    // Edge cases — remove participant
    // =========================================================================

    /** @test */
    public function test_remove_participant_from_non_existent_conversation_returns_404(): void
    {
        $admin = $this->chatNormalUser();
        $other = $this->chatNormalUser();

        $this->actingAs($admin);

        $this->deleteJson("/api/v3/chat/conversations/99999/participants/{$other->id}")
            ->assertNotFound();
    }
}
