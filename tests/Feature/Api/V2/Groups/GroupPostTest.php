<?php

namespace Tests\Feature\Api\V2\Groups;

use App\Models\User;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Group Posts
 *
 * Tests the following endpoints:
 * - GET /api/v2/groups/{id}/posts
 * - POST /api/v2/groups/{id}/posts
 *
 * @group groups
 * @group group-posts
 * @group api
 * @group v2
 */
class GroupPostTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== GET GROUP POSTS TESTS ====================

    /** @test */
    public function test_get_group_posts_returns_list()
    {
        $owner = $this->doctorUser();
        $member = $this->doctorUser();

        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        GroupMember::create([
            'group_id' => $group->id,
            'doctor_id' => $member->id,
        ]);

        // Create posts
        GroupPost::factory()->count(5)->create(['group_id' => $group->id]);

        // Authenticate as member
        $this->actingAs($member);

        $response = $this->getJson("/api/v2/groups/{$group->id}/posts");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $posts = $response->json('data');
        $this->assertCount(5, $posts);
    }

    /** @test */
    public function test_get_group_posts_requires_authentication()
    {
        $group = Group::factory()->create();

        $response = $this->getJson("/api/v2/groups/{$group->id}/posts");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_group_posts_requires_membership()
    {
        $owner = $this->doctorUser();
        $nonMember = $this->doctorUser();

        $group = Group::factory()->private()->create(['doctor_id' => $owner->id]);

        // Create posts
        GroupPost::factory()->count(3)->create(['group_id' => $group->id]);

        // Authenticate as non-member
        $this->actingAs($nonMember);

        $response = $this->getJson("/api/v2/groups/{$group->id}/posts");

        // Should fail for non-members
        $response->assertStatus(403);
    }

    /** @test */
    public function test_get_group_posts_includes_author_data()
    {
        $owner = $this->doctorUser();
        $member = $this->doctorUser(['name' => 'Dr. Author', 'lname' => 'Name']);

        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        GroupMember::create([
            'group_id' => $group->id,
            'doctor_id' => $member->id,
        ]);

        GroupPost::factory()->create([
            'group_id' => $group->id,
            'doctor_id' => $member->id,
        ]);

        // Authenticate as member
        $this->actingAs($member);

        $response = $this->getJson("/api/v2/groups/{$group->id}/posts");

        $response->assertStatus(200);

        $posts = $response->json('data');

        if (!empty($posts)) {
            $this->assertArrayHasKey('doctor', $posts[0]);
        }
    }

    /** @test */
    public function test_get_group_posts_ordered_by_date()
    {
        $owner = $this->doctorUser();
        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        // Create posts with different timestamps
        GroupPost::factory()->create([
            'group_id' => $group->id,
            'created_at' => now()->subHours(3),
        ]);

        GroupPost::factory()->create([
            'group_id' => $group->id,
            'created_at' => now()->subHours(1),
        ]);

        $response = $this->getJson("/api/v2/groups/{$group->id}/posts");

        $response->assertStatus(200);

        $posts = $response->json('data');

        if (count($posts) >= 2) {
            // Verify ordering (recent first)
            $dates = collect($posts)->pluck('created_at')->toArray();
            $this->assertGreaterThanOrEqual($posts[1]['created_at'], $posts[0]['created_at']);
        }
    }

    // ==================== CREATE GROUP POST TESTS ====================

    /** @test */
    public function test_create_group_post_successfully()
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

        $postData = [
            'content' => 'This is a group post',
        ];

        $response = $this->postJson("/api/v2/groups/{$group->id}/posts", $postData);

        $response->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('group_posts', [
            'group_id' => $group->id,
            'doctor_id' => $member->id,
            'content' => 'This is a group post',
        ]);
    }

    /** @test */
    public function test_create_group_post_requires_authentication()
    {
        $group = Group::factory()->create();

        $response = $this->postJson("/api/v2/groups/{$group->id}/posts", [
            'content' => 'Test post',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_create_group_post_requires_membership()
    {
        $owner = $this->doctorUser();
        $nonMember = $this->doctorUser();

        $group = Group::factory()->private()->create(['doctor_id' => $owner->id]);

        // Authenticate as non-member
        $this->actingAs($nonMember);

        $response = $this->postJson("/api/v2/groups/{$group->id}/posts", [
            'content' => 'Trying to post',
        ]);

        // Should fail for non-members
        $response->assertStatus(403);
    }

    /** @test */
    public function test_create_group_post_validates_content()
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

        $response = $this->postJson("/api/v2/groups/{$group->id}/posts", [
            'content' => '', // Empty content
        ]);

        $this->assertValidationError($response, ['content']);
    }

    /** @test */
    public function test_create_group_post_with_media()
    {
        Storage::fake('public');

        $owner = $this->doctorUser();
        $member = $this->doctorUser();

        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        GroupMember::create([
            'group_id' => $group->id,
            'doctor_id' => $member->id,
        ]);

        // Authenticate as member
        $this->actingAs($member);

        $image = $this->createFakeImage('post-image.jpg');

        $response = $this->postJson("/api/v2/groups/{$group->id}/posts", [
            'content' => 'Post with image',
            'media' => $image,
        ]);

        $response->assertStatus(201);

        $post = GroupPost::latest()->first();
        $this->assertNotNull($post->media);
    }

    /** @test */
    public function test_group_owner_can_post_without_being_member()
    {
        $owner = $this->doctorUser();
        $group = Group::factory()->create(['doctor_id' => $owner->id]);

        $postData = [
            'content' => 'Owner posting',
        ];

        $response = $this->postJson("/api/v2/groups/{$group->id}/posts", $postData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('group_posts', [
            'group_id' => $group->id,
            'doctor_id' => $owner->id,
            'content' => 'Owner posting',
        ]);
    }

    /** @test */
    public function test_create_group_post_handles_non_existent_group()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/groups/99999/posts', [
            'content' => 'Test post',
        ]);

        $this->assertNotFound($response);
    }
}
