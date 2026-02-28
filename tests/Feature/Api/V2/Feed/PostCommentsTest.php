<?php

namespace Tests\Feature\Api\V2\Feed;

use App\Models\User;
use App\Models\Posts;
use App\Models\PostComments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Legacy Post Comments (Postcomments endpoint)
 *
 * Tests the following endpoints:
 * - GET /api/v2/Postcomments/{id}
 * - POST /api/v2/Postcomments
 * - DELETE /api/v2/Postcomments/{id}
 *
 * @group feed
 * @group post-comments
 * @group legacy
 * @group api
 * @group v2
 */
class PostCommentsTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== GET POST COMMENTS TESTS ====================

    /** @test */
    public function test_get_post_comments_legacy_endpoint()
    {
        $doctor = $this->doctorUser();
        $post = Posts::factory()->create();

        // Create comments
        PostComments::factory()->count(3)->create(['post_id' => $post->id]);

        $response = $this->getJson("/api/v2/Postcomments/{$post->id}");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $comments = $response->json('data');
        $this->assertCount(3, $comments);
    }

    /** @test */
    public function test_get_post_comments_requires_authentication()
    {
        $post = Posts::factory()->create();

        $response = $this->getJson("/api/v2/Postcomments/{$post->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_post_comments_returns_correct_structure()
    {
        $doctor = $this->doctorUser();
        $post = Posts::factory()->create();

        PostComments::factory()->create([
            'post_id' => $post->id,
            'doctor_id' => $doctor->id,
        ]);

        $response = $this->getJson("/api/v2/Postcomments/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'value',
                'message',
                'data' => [
                    '*' => ['id', 'post_id', 'doctor_id', 'content'],
                ],
            ]);
    }

    /** @test */
    public function test_post_comments_handles_non_existent_post()
    {
        $doctor = $this->doctorUser();

        $response = $this->getJson('/api/v2/Postcomments/99999');

        // Should return empty array or 404
        $response->assertStatus(200);

        $comments = $response->json('data');
        $this->assertEmpty($comments);
    }

    // ==================== CREATE POST COMMENT TESTS ====================

    /** @test */
    public function test_create_post_comment_legacy_endpoint()
    {
        $doctor = $this->doctorUser();
        $post = Posts::factory()->create();

        $commentData = [
            'post_id' => $post->id,
            'content' => 'Legacy comment test',
        ];

        $response = $this->postJson('/api/v2/Postcomments', $commentData);

        $response->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('post_comments', [
            'post_id' => $post->id,
            'content' => 'Legacy comment test',
        ]);
    }

    /** @test */
    public function test_create_post_comment_validates_data()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/Postcomments', [
            'post_id' => null,
            'content' => '',
        ]);

        $this->assertValidationError($response, ['post_id', 'content']);
    }

    /** @test */
    public function test_create_post_comment_requires_authentication()
    {
        $post = Posts::factory()->create();

        $response = $this->postJson('/api/v2/Postcomments', [
            'post_id' => $post->id,
            'content' => 'Test comment',
        ]);

        $this->assertUnauthenticated($response);
    }

    // ==================== DELETE POST COMMENT TESTS ====================

    /** @test */
    public function test_delete_post_comment_legacy_endpoint()
    {
        $doctor = $this->doctorUser();
        $post = Posts::factory()->create();

        $comment = PostComments::factory()->create([
            'post_id' => $post->id,
            'doctor_id' => $doctor->id,
        ]);

        $response = $this->deleteJson("/api/v2/Postcomments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseMissing('post_comments', [
            'id' => $comment->id,
        ]);
    }

    /** @test */
    public function test_delete_post_comment_requires_ownership()
    {
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        $comment = PostComments::factory()->create([
            'doctor_id' => $doctor2->id,
        ]);

        // Authenticate as doctor1
        $this->actingAs($doctor1);

        $response = $this->deleteJson("/api/v2/Postcomments/{$comment->id}");

        // Should fail due to ownership
        $response->assertStatus(403);
    }

    /** @test */
    public function test_post_comments_endpoint_backward_compatible()
    {
        $doctor = $this->doctorUser();
        $post = Posts::factory()->create();

        // Create comment using legacy endpoint
        $this->postJson('/api/v2/Postcomments', [
            'post_id' => $post->id,
            'content' => 'Backward compatible comment',
        ]);

        // Retrieve using legacy endpoint
        $response = $this->getJson("/api/v2/Postcomments/{$post->id}");

        $response->assertStatus(200);

        $comments = $response->json('data');
        $this->assertNotEmpty($comments);

        $foundComment = collect($comments)->firstWhere('content', 'Backward compatible comment');
        $this->assertNotNull($foundComment);
    }
}
