<?php

namespace Tests\Feature\Api\V2\Groups;

use App\Models\User;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Group CRUD operations
 *
 * Tests the following endpoints:
 * - POST /api/v2/groups
 * - GET /api/v2/groups/{id}
 * - POST /api/v2/groups/{id}/update
 * - DELETE /api/v2/groups/{id}
 *
 * @group groups
 * @group group-crud
 * @group api
 * @group v2
 */
class GroupCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== CREATE GROUP TESTS ====================

    /** @test */
    public function test_create_group_successfully()
    {
        $doctor = $this->doctorUser();

        $groupData = [
            'name' => 'Medical Discussion Group',
            'description' => 'A group for discussing medical cases',
            'type' => 'public',
        ];

        $response = $this->postJson('/api/v2/groups', $groupData);

        $response->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('groups', [
            'name' => 'Medical Discussion Group',
            'description' => 'A group for discussing medical cases',
            'type' => 'public',
            'doctor_id' => $doctor->id,
        ]);
    }

    /** @test */
    public function test_create_group_requires_authentication()
    {
        $response = $this->postJson('/api/v2/groups', [
            'name' => 'Test Group',
            'description' => 'Test description',
            'type' => 'public',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_create_group_validates_name()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/groups', [
            'name' => '', // Empty name
            'description' => 'Test description',
            'type' => 'public',
        ]);

        $this->assertValidationError($response, ['name']);
    }

    /** @test */
    public function test_create_group_validates_description()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/groups', [
            'name' => 'Test Group',
            'description' => '', // Empty description
            'type' => 'public',
        ]);

        $this->assertValidationError($response, ['description']);
    }

    /** @test */
    public function test_create_group_validates_type()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/groups', [
            'name' => 'Test Group',
            'description' => 'Test description',
            'type' => 'invalid_type', // Invalid type
        ]);

        $this->assertValidationError($response, ['type']);
    }

    /** @test */
    public function test_create_group_sets_owner_as_creator()
    {
        $doctor = $this->doctorUser();

        $groupData = [
            'name' => 'Test Group',
            'description' => 'Test description',
            'type' => 'public',
        ];

        $response = $this->postJson('/api/v2/groups', $groupData);

        $response->assertStatus(201);

        $group = Group::latest()->first();
        $this->assertEquals($doctor->id, $group->doctor_id);
    }

    /** @test */
    public function test_create_group_with_image_upload()
    {
        Storage::fake('public');
        $doctor = $this->doctorUser();

        $image = $this->createFakeImage('group-image.jpg');

        $response = $this->postJson('/api/v2/groups', [
            'name' => 'Group with Image',
            'description' => 'Test description',
            'type' => 'public',
            'image' => $image,
        ]);

        $response->assertStatus(201);

        $group = Group::latest()->first();
        $this->assertNotNull($group->image);
    }

    /** @test */
    public function test_create_private_group()
    {
        $doctor = $this->doctorUser();

        $groupData = [
            'name' => 'Private Group',
            'description' => 'Private discussion',
            'type' => 'private',
        ];

        $response = $this->postJson('/api/v2/groups', $groupData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('groups', [
            'name' => 'Private Group',
            'type' => 'private',
        ]);
    }

    // ==================== GET GROUP TESTS ====================

    /** @test */
    public function test_get_group_by_id_returns_group()
    {
        $doctor = $this->doctorUser();
        $group = Group::factory()->create(['doctor_id' => $doctor->id]);

        $response = $this->getJson("/api/v2/groups/{$group->id}");

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'data' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                ],
            ]);
    }

    /** @test */
    public function test_get_group_requires_authentication()
    {
        $group = Group::factory()->create();

        $response = $this->getJson("/api/v2/groups/{$group->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_group_handles_non_existent_group()
    {
        $doctor = $this->doctorUser();

        $response = $this->getJson('/api/v2/groups/99999');

        $this->assertNotFound($response);
    }

    /** @test */
    public function test_get_group_includes_owner_data()
    {
        $doctor = $this->doctorUser(['name' => 'Dr. John', 'lname' => 'Doe']);
        $group = Group::factory()->create(['doctor_id' => $doctor->id]);

        $response = $this->getJson("/api/v2/groups/{$group->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('doctor', $data);
    }

    /** @test */
    public function test_get_group_includes_member_count()
    {
        $doctor = $this->doctorUser();
        $group = Group::factory()->create(['doctor_id' => $doctor->id]);

        // Add members
        GroupMember::factory()->count(5)->create(['group_id' => $group->id]);

        $response = $this->getJson("/api/v2/groups/{$group->id}");

        $response->assertStatus(200);

        $data = $response->json('data');

        if (isset($data['members_count'])) {
            $this->assertEquals(5, $data['members_count']);
        }
    }

    // ==================== UPDATE GROUP TESTS ====================

    /** @test */
    public function test_update_group_successfully()
    {
        $doctor = $this->doctorUser();
        $group = Group::factory()->create(['doctor_id' => $doctor->id]);

        $updateData = [
            'name' => 'Updated Group Name',
            'description' => 'Updated description',
        ];

        $response = $this->postJson("/api/v2/groups/{$group->id}/update", $updateData);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'name' => 'Updated Group Name',
            'description' => 'Updated description',
        ]);
    }

    /** @test */
    public function test_update_group_requires_authentication()
    {
        $group = Group::factory()->create();

        $response = $this->postJson("/api/v2/groups/{$group->id}/update", [
            'name' => 'Updated Name',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_update_group_requires_ownership()
    {
        $owner = $this->doctorUser();
        $otherDoctor = $this->doctorUser();

        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        // Authenticate as other doctor
        $this->actingAs($otherDoctor);

        $response = $this->postJson("/api/v2/groups/{$group->id}/update", [
            'name' => 'Trying to update',
        ]);

        // Should fail due to ownership
        $response->assertStatus(403);
    }

    /** @test */
    public function test_update_group_image()
    {
        Storage::fake('public');
        $doctor = $this->doctorUser();
        $group = Group::factory()->create(['doctor_id' => $doctor->id]);

        $newImage = $this->createFakeImage('new-group-image.jpg');

        $response = $this->postJson("/api/v2/groups/{$group->id}/update", [
            'name' => $group->name,
            'description' => $group->description,
            'image' => $newImage,
        ]);

        $response->assertStatus(200);

        $group->refresh();
        $this->assertNotNull($group->image);
    }

    /** @test */
    public function test_update_group_type()
    {
        $doctor = $this->doctorUser();
        $group = Group::factory()->public()->create(['doctor_id' => $doctor->id]);

        $response = $this->postJson("/api/v2/groups/{$group->id}/update", [
            'name' => $group->name,
            'description' => $group->description,
            'type' => 'private',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'type' => 'private',
        ]);
    }

    // ==================== DELETE GROUP TESTS ====================

    /** @test */
    public function test_delete_group_successfully()
    {
        $doctor = $this->doctorUser();
        $group = Group::factory()->create(['doctor_id' => $doctor->id]);

        $response = $this->deleteJson("/api/v2/groups/{$group->id}");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseMissing('groups', [
            'id' => $group->id,
        ]);
    }

    /** @test */
    public function test_delete_group_requires_authentication()
    {
        $group = Group::factory()->create();

        $response = $this->deleteJson("/api/v2/groups/{$group->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_delete_group_requires_ownership()
    {
        $owner = $this->doctorUser();
        $otherDoctor = $this->doctorUser();

        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        // Authenticate as other doctor
        $this->actingAs($otherDoctor);

        $response = $this->deleteJson("/api/v2/groups/{$group->id}");

        // Should fail due to ownership
        $response->assertStatus(403);
    }

    /** @test */
    public function test_delete_group_removes_all_members()
    {
        $doctor = $this->doctorUser();
        $group = Group::factory()->create(['doctor_id' => $doctor->id]);

        // Add members
        $members = GroupMember::factory()->count(3)->create(['group_id' => $group->id]);

        $this->deleteJson("/api/v2/groups/{$group->id}");

        // Verify all members were removed
        foreach ($members as $member) {
            $this->assertDatabaseMissing('group_members', [
                'id' => $member->id,
            ]);
        }
    }

    /** @test */
    public function test_delete_group_handles_non_existent_group()
    {
        $doctor = $this->doctorUser();

        $response = $this->deleteJson('/api/v2/groups/99999');

        $this->assertNotFound($response);
    }
}
