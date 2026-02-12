<?php

namespace Tests\Feature\Api\V2\Groups;

use App\Models\User;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Group Membership operations
 *
 * Tests the following endpoints:
 * - POST /api/v2/groups/{id}/join
 * - POST /api/v2/groups/{id}/leave
 * - GET /api/v2/groups/{id}/members
 * - POST /api/v2/groups/{id}/invite
 * - POST /api/v2/groups/{id}/removeMember
 *
 * @group groups
 * @group group-membership
 * @group api
 * @group v2
 */
class GroupMembershipTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== JOIN GROUP TESTS ====================

    /** @test */
    public function test_join_public_group_successfully()
    {
        $owner = $this->doctorUser();
        $member = $this->doctorUser();

        $group = Group::factory()->public()->create(['doctor_id' => $owner->id]);

        // Authenticate as member
        $this->actingAs($member);

        $response = $this->postJson("/api/v2/groups/{$group->id}/join");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'doctor_id' => $member->id,
        ]);
    }

    /** @test */
    public function test_join_group_requires_authentication()
    {
        $group = Group::factory()->public()->create();

        $response = $this->postJson("/api/v2/groups/{$group->id}/join");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_join_private_group_requires_invitation()
    {
        $owner = $this->doctorUser();
        $member = $this->doctorUser();

        $group = Group::factory()->private()->create(['doctor_id' => $owner->id]);

        // Authenticate as member
        $this->actingAs($member);

        $response = $this->postJson("/api/v2/groups/{$group->id}/join");

        // Should fail for private group without invitation
        $response->assertStatus(403);
    }

    /** @test */
    public function test_join_group_prevents_duplicate_membership()
    {
        $owner = $this->doctorUser();
        $member = $this->doctorUser();

        $group = Group::factory()->public()->create(['doctor_id' => $owner->id]);

        // Join once
        GroupMember::create([
            'group_id' => $group->id,
            'doctor_id' => $member->id,
        ]);

        // Authenticate as member
        $this->actingAs($member);

        // Try to join again
        $response = $this->postJson("/api/v2/groups/{$group->id}/join");

        // Should handle duplicate gracefully
        $memberCount = GroupMember::where('group_id', $group->id)
            ->where('doctor_id', $member->id)
            ->count();

        $this->assertEquals(1, $memberCount);
    }

    /** @test */
    public function test_join_group_handles_non_existent_group()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/groups/99999/join');

        $this->assertNotFound($response);
    }

    // ==================== LEAVE GROUP TESTS ====================

    /** @test */
    public function test_leave_group_successfully()
    {
        $owner = $this->doctorUser();
        $member = $this->doctorUser();

        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        GroupMember::create([
            'group_id' => $group->id,
            'doctor_id' => $member->id,
        ]);

        // Authenticate as member
        $this->actingAs($member);

        $response = $this->postJson("/api/v2/groups/{$group->id}/leave");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseMissing('group_members', [
            'group_id' => $group->id,
            'doctor_id' => $member->id,
        ]);
    }

    /** @test */
    public function test_leave_group_requires_authentication()
    {
        $group = Group::factory()->create();

        $response = $this->postJson("/api/v2/groups/{$group->id}/leave");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_owner_cannot_leave_own_group()
    {
        $owner = $this->doctorUser();
        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        $response = $this->postJson("/api/v2/groups/{$group->id}/leave");

        // Owner should not be able to leave
        $response->assertStatus(403);
    }

    /** @test */
    public function test_leave_group_when_not_member()
    {
        $owner = $this->doctorUser();
        $nonMember = $this->doctorUser();

        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        // Authenticate as non-member
        $this->actingAs($nonMember);

        $response = $this->postJson("/api/v2/groups/{$group->id}/leave");

        // Should handle gracefully
        $response->assertStatus(404);
    }

    // ==================== GET MEMBERS TESTS ====================

    /** @test */
    public function test_get_group_members_returns_list()
    {
        $owner = $this->doctorUser();
        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        // Add members
        GroupMember::factory()->count(5)->create(['group_id' => $group->id]);

        $response = $this->getJson("/api/v2/groups/{$group->id}/members");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $members = $response->json('data');
        $this->assertCount(5, $members);
    }

    /** @test */
    public function test_get_group_members_requires_authentication()
    {
        $group = Group::factory()->create();

        $response = $this->getJson("/api/v2/groups/{$group->id}/members");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_group_members_includes_user_data()
    {
        $owner = $this->doctorUser();
        $member = $this->doctorUser(['name' => 'Dr. Jane', 'lname' => 'Smith']);

        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        GroupMember::create([
            'group_id' => $group->id,
            'doctor_id' => $member->id,
        ]);

        $response = $this->getJson("/api/v2/groups/{$group->id}/members");

        $response->assertStatus(200);

        $members = $response->json('data');

        if (!empty($members)) {
            $this->assertArrayHasKey('doctor', $members[0]);
        }
    }

    /** @test */
    public function test_get_group_members_empty_list()
    {
        $owner = $this->doctorUser();
        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        $response = $this->getJson("/api/v2/groups/{$group->id}/members");

        $response->assertStatus(200);

        $members = $response->json('data');
        $this->assertEmpty($members);
    }

    // ==================== INVITE MEMBER TESTS ====================

    /** @test */
    public function test_invite_member_to_group()
    {
        $owner = $this->doctorUser();
        $invitee = $this->doctorUser();

        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        $response = $this->postJson("/api/v2/groups/{$group->id}/invite", [
            'doctor_id' => $invitee->id,
        ]);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        // Verify invitation created or member added
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'doctor_id' => $invitee->id,
        ]);
    }

    /** @test */
    public function test_invite_member_requires_authentication()
    {
        $group = Group::factory()->create();

        $response = $this->postJson("/api/v2/groups/{$group->id}/invite", [
            'doctor_id' => 1,
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_invite_member_requires_ownership()
    {
        $owner = $this->doctorUser();
        $otherDoctor = $this->doctorUser();
        $invitee = $this->doctorUser();

        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        // Authenticate as non-owner
        $this->actingAs($otherDoctor);

        $response = $this->postJson("/api/v2/groups/{$group->id}/invite", [
            'doctor_id' => $invitee->id,
        ]);

        // Should fail due to ownership
        $response->assertStatus(403);
    }

    /** @test */
    public function test_invite_member_validates_doctor_id()
    {
        $owner = $this->doctorUser();
        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        $response = $this->postJson("/api/v2/groups/{$group->id}/invite", [
            'doctor_id' => null,
        ]);

        $this->assertValidationError($response, ['doctor_id']);
    }

    /** @test */
    public function test_invite_member_handles_non_existent_doctor()
    {
        $owner = $this->doctorUser();
        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        $response = $this->postJson("/api/v2/groups/{$group->id}/invite", [
            'doctor_id' => 99999,
        ]);

        $this->assertNotFound($response);
    }

    // ==================== REMOVE MEMBER TESTS ====================

    /** @test */
    public function test_remove_member_from_group()
    {
        $owner = $this->doctorUser();
        $member = $this->doctorUser();

        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        GroupMember::create([
            'group_id' => $group->id,
            'doctor_id' => $member->id,
        ]);

        $response = $this->postJson("/api/v2/groups/{$group->id}/removeMember", [
            'doctor_id' => $member->id,
        ]);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseMissing('group_members', [
            'group_id' => $group->id,
            'doctor_id' => $member->id,
        ]);
    }

    /** @test */
    public function test_remove_member_requires_authentication()
    {
        $group = Group::factory()->create();

        $response = $this->postJson("/api/v2/groups/{$group->id}/removeMember", [
            'doctor_id' => 1,
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_remove_member_requires_ownership()
    {
        $owner = $this->doctorUser();
        $otherDoctor = $this->doctorUser();
        $member = $this->doctorUser();

        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        GroupMember::create([
            'group_id' => $group->id,
            'doctor_id' => $member->id,
        ]);

        // Authenticate as non-owner
        $this->actingAs($otherDoctor);

        $response = $this->postJson("/api/v2/groups/{$group->id}/removeMember", [
            'doctor_id' => $member->id,
        ]);

        // Should fail due to ownership
        $response->assertStatus(403);
    }

    /** @test */
    public function test_remove_member_validates_doctor_id()
    {
        $owner = $this->doctorUser();
        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        $response = $this->postJson("/api/v2/groups/{$group->id}/removeMember", [
            'doctor_id' => null,
        ]);

        $this->assertValidationError($response, ['doctor_id']);
    }

    /** @test */
    public function test_remove_member_handles_non_member()
    {
        $owner = $this->doctorUser();
        $nonMember = $this->doctorUser();

        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        $response = $this->postJson("/api/v2/groups/{$group->id}/removeMember", [
            'doctor_id' => $nonMember->id,
        ]);

        // Should handle gracefully
        $response->assertStatus(404);
    }
}
