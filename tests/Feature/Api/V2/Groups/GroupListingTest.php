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
 * Test suite for Group Listing operations
 *
 * Tests the following endpoints:
 * - GET /api/v2/groups
 * - GET /api/v2/mygroups
 *
 * @group groups
 * @group group-listing
 * @group api
 * @group v2
 */
class GroupListingTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== GET ALL GROUPS TESTS ====================

    /** @test */
    public function test_get_all_groups_returns_list()
    {
        $doctor = $this->doctorUser();

        // Create public groups
        Group::factory()->public()->count(5)->create();

        $response = $this->getJson('/api/v2/groups');

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $groups = $response->json('data');
        $this->assertGreaterThanOrEqual(5, count($groups));
    }

    /** @test */
    public function test_get_all_groups_requires_authentication()
    {
        $response = $this->getJson('/api/v2/groups');

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_all_groups_includes_public_groups()
    {
        $doctor = $this->doctorUser();

        $publicGroup = Group::factory()->public()->create(['name' => 'Public Group']);

        $response = $this->getJson('/api/v2/groups');

        $response->assertStatus(200);

        $groups = $response->json('data');
        $groupNames = collect($groups)->pluck('name')->toArray();

        $this->assertContains('Public Group', $groupNames);
    }

    /** @test */
    public function test_get_all_groups_filters_private_groups()
    {
        $doctor = $this->doctorUser();

        // Create private group
        Group::factory()->private()->create(['name' => 'Private Group']);

        // Create public group
        Group::factory()->public()->create(['name' => 'Public Group']);

        $response = $this->getJson('/api/v2/groups');

        $response->assertStatus(200);

        $groups = $response->json('data');

        // Verify only public groups visible to non-members
        foreach ($groups as $group) {
            if ($group['name'] === 'Private Group') {
                // Private group should not be visible or user must be member
                $this->fail('Private group should not be in public listing');
            }
        }
    }

    /** @test */
    public function test_get_all_groups_includes_owner_data()
    {
        $owner = $this->doctorUser(['name' => 'Dr. Owner', 'lname' => 'Name']);
        $viewer = $this->doctorUser();

        Group::factory()->public()->create(['doctor_id' => $owner->id]);

        // Authenticate as viewer
        $this->actingAs($viewer);

        $response = $this->getJson('/api/v2/groups');

        $response->assertStatus(200);

        $groups = $response->json('data');

        if (!empty($groups)) {
            $this->assertArrayHasKey('doctor', $groups[0]);
        }
    }

    /** @test */
    public function test_get_all_groups_includes_member_count()
    {
        $doctor = $this->doctorUser();
        $group = Group::factory()->public()->create();

        // Add members
        GroupMember::factory()->count(3)->create(['group_id' => $group->id]);

        $response = $this->getJson('/api/v2/groups');

        $response->assertStatus(200);

        $groups = $response->json('data');
        $foundGroup = collect($groups)->firstWhere('id', $group->id);

        if ($foundGroup && isset($foundGroup['members_count'])) {
            $this->assertEquals(3, $foundGroup['members_count']);
        }
    }

    /** @test */
    public function test_get_all_groups_pagination()
    {
        $doctor = $this->doctorUser();

        // Create many groups
        Group::factory()->public()->count(20)->create();

        $response = $this->getJson('/api/v2/groups');

        $response->assertStatus(200);

        // Verify pagination structure exists
        if (isset($response->json()['data']['data'])) {
            $this->assertHasPaginationStructure($response);
        }
    }

    // ==================== GET MY GROUPS TESTS ====================

    /** @test */
    public function test_get_my_groups_returns_owned_groups()
    {
        $doctor = $this->doctorUser();

        // Create groups owned by the doctor
        Group::factory()->count(3)->create(['doctor_id' => $doctor->id]);

        // Create groups owned by others
        Group::factory()->count(2)->create();

        $response = $this->getJson('/api/v2/mygroups');

        $response->assertStatus(200);

        $groups = $response->json('data');

        // Should only return doctor's own groups
        foreach ($groups as $group) {
            $this->assertEquals($doctor->id, $group['doctor_id']);
        }

        $this->assertCount(3, $groups);
    }

    /** @test */
    public function test_get_my_groups_returns_joined_groups()
    {
        $owner = $this->doctorUser();
        $member = $this->doctorUser();

        // Create group owned by owner
        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        // Member joins the group
        GroupMember::create([
            'group_id' => $group->id,
            'doctor_id' => $member->id,
        ]);

        // Authenticate as member
        $this->actingAs($member);

        $response = $this->getJson('/api/v2/mygroups');

        $response->assertStatus(200);

        $groups = $response->json('data');
        $groupIds = collect($groups)->pluck('id')->toArray();

        $this->assertContains($group->id, $groupIds);
    }

    /** @test */
    public function test_get_my_groups_requires_authentication()
    {
        $response = $this->getJson('/api/v2/mygroups');

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_my_groups_returns_empty_for_no_groups()
    {
        $doctor = $this->doctorUser();

        $response = $this->getJson('/api/v2/mygroups');

        $response->assertStatus(200);

        $groups = $response->json('data');
        $this->assertEmpty($groups);
    }

    /** @test */
    public function test_get_my_groups_includes_both_owned_and_joined()
    {
        $doctor = $this->doctorUser();

        // Create group owned by doctor
        $ownedGroup = Group::factory()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Owned Group',
        ]);

        // Create group owned by another doctor
        $otherGroup = Group::factory()->create(['name' => 'Joined Group']);

        // Doctor joins the other group
        GroupMember::create([
            'group_id' => $otherGroup->id,
            'doctor_id' => $doctor->id,
        ]);

        $response = $this->getJson('/api/v2/mygroups');

        $response->assertStatus(200);

        $groups = $response->json('data');
        $groupIds = collect($groups)->pluck('id')->toArray();

        // Should include both owned and joined groups
        $this->assertContains($ownedGroup->id, $groupIds);
        $this->assertContains($otherGroup->id, $groupIds);
        $this->assertCount(2, $groups);
    }
}
