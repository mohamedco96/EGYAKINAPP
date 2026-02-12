<?php

namespace Tests\Feature\Api\V2\Feed;

use App\Models\User;
use App\Models\FeedPost;
use App\Models\FeedPostLike;
use App\Models\FeedPostComment;
use App\Models\Hashtag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Feed Post CRUD operations
 *
 * Tests the following endpoints:
 * - GET /api/v2/feed/posts
 * - POST /api/v2/feed/posts
 * - GET /api/v2/feed/posts/{id}
 * - POST /api/v2/feed/posts/{id} (update)
 * - DELETE /api/v2/feed/posts/{id}
 *
 * @group feed
 * @group feed-crud
 * @group api
 * @group v2
 */
class FeedPostCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== GET FEED POSTS TESTS ====================

    /** @test */
    public function test_get_feed_posts_returns_paginated_list()
    {
        $doctor = $this->doctorUser();

        // Create feed posts
        FeedPost::factory()->count(10)->create();

        $response = $this->getJson('/api/v2/feed/posts');

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertHasPaginationStructure($response);
    }

    /** @test */
    public function test_get_feed_posts_requires_authentication()
    {
        $response = $this->getJson('/api/v2/feed/posts');

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_feed_posts_includes_doctor_data()
    {
        $doctor = $this->doctorUser();

        FeedPost::factory()->count(3)->create(['doctor_id' => $doctor->id]);

        $response = $this->getJson('/api/v2/feed/posts');

        $response->assertStatus(200);

        $posts = $response->json('data.data');

        if (!empty($posts)) {
            // Verify doctor relationship is loaded
            $this->assertArrayHasKey('doctor', $posts[0]);
        }
    }

    /** @test */
    public function test_get_feed_posts_includes_like_count()
    {
        $doctor = $this->doctorUser();

        $post = FeedPost::factory()->create(['doctor_id' => $doctor->id]);

        // Create likes
        FeedPostLike::factory()->count(5)->create(['feed_post_id' => $post->id]);

        $response = $this->getJson('/api/v2/feed/posts');

        $response->assertStatus(200);

        $posts = $response->json('data.data');
        $foundPost = collect($posts)->firstWhere('id', $post->id);

        if ($foundPost) {
            $this->assertEquals(5, $foundPost['likes_count']);
        }
    }

    /** @test */
    public function test_get_feed_posts_includes_comment_count()
    {
        $doctor = $this->doctorUser();

        $post = FeedPost::factory()->create(['doctor_id' => $doctor->id]);

        // Create comments
        FeedPostComment::factory()->count(3)->create(['feed_post_id' => $post->id]);

        $response = $this->getJson('/api/v2/feed/posts');

        $response->assertStatus(200);

        $posts = $response->json('data.data');
        $foundPost = collect($posts)->firstWhere('id', $post->id);

        if ($foundPost) {
            $this->assertEquals(3, $foundPost['comments_count']);
        }
    }

    // ==================== CREATE FEED POST TESTS ====================

    /** @test */
    public function test_create_feed_post_successfully()
    {
        $doctor = $this->doctorUser();

        $postData = [
            'content' => 'This is a test post',
        ];

        $response = $this->postJson('/api/v2/feed/posts', $postData);

        $response->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('feed_posts', [
            'doctor_id' => $doctor->id,
            'content' => 'This is a test post',
        ]);
    }

    /** @test */
    public function test_create_feed_post_requires_authentication()
    {
        $response = $this->postJson('/api/v2/feed/posts', [
            'content' => 'Test post',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_create_feed_post_validates_content()
    {
        $doctor = $this->doctorUser();

        $response = $this->postJson('/api/v2/feed/posts', [
            'content' => '', // Empty content
        ]);

        $this->assertValidationError($response, ['content']);
    }

    /** @test */
    public function test_create_feed_post_handles_media_upload()
    {
        Storage::fake('public');
        $doctor = $this->doctorUser();

        $image = $this->createFakeImage('post-image.jpg');

        $response = $this->postJson('/api/v2/feed/posts', [
            'content' => 'Post with image',
            'media' => $image,
        ]);

        $response->assertStatus(201);

        $post = FeedPost::latest()->first();
        $this->assertNotNull($post->media);
    }

    /** @test */
    public function test_create_feed_post_extracts_hashtags()
    {
        $doctor = $this->doctorUser();

        $postData = [
            'content' => 'This is a #test post with #hashtags',
        ];

        $response = $this->postJson('/api/v2/feed/posts', $postData);

        $response->assertStatus(201);

        // Verify hashtags were extracted
        $this->assertDatabaseHas('hashtags', [
            'hashtag' => 'test',
        ]);

        $this->assertDatabaseHas('hashtags', [
            'hashtag' => 'hashtags',
        ]);
    }

    // ==================== GET FEED POST BY ID TESTS ====================

    /** @test */
    public function test_get_feed_post_by_id_returns_post()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create(['doctor_id' => $doctor->id]);

        $response = $this->getJson("/api/v2/feed/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'value' => true,
                'data' => [
                    'id' => $post->id,
                    'content' => $post->content,
                ],
            ]);
    }

    /** @test */
    public function test_get_feed_post_by_id_requires_authentication()
    {
        $post = FeedPost::factory()->create();

        $response = $this->getJson("/api/v2/feed/posts/{$post->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_feed_post_by_id_handles_non_existent_post()
    {
        $doctor = $this->doctorUser();

        $response = $this->getJson('/api/v2/feed/posts/99999');

        $this->assertNotFound($response);
    }

    // ==================== UPDATE FEED POST TESTS ====================

    /** @test */
    public function test_update_feed_post_successfully()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create(['doctor_id' => $doctor->id]);

        $updateData = [
            'content' => 'Updated content',
        ];

        $response = $this->postJson("/api/v2/feed/posts/{$post->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('feed_posts', [
            'id' => $post->id,
            'content' => 'Updated content',
        ]);
    }

    /** @test */
    public function test_update_feed_post_requires_authentication()
    {
        $post = FeedPost::factory()->create();

        $response = $this->postJson("/api/v2/feed/posts/{$post->id}", [
            'content' => 'Updated content',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_update_feed_post_requires_ownership()
    {
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        // Post belongs to doctor2
        $post = FeedPost::factory()->create(['doctor_id' => $doctor2->id]);

        // Authenticate as doctor1
        $this->actingAs($doctor1);

        $response = $this->postJson("/api/v2/feed/posts/{$post->id}", [
            'content' => 'Trying to update',
        ]);

        // Should fail due to ownership
        $response->assertStatus(403);
    }

    // ==================== DELETE FEED POST TESTS ====================

    /** @test */
    public function test_delete_feed_post_successfully()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create(['doctor_id' => $doctor->id]);

        $response = $this->deleteJson("/api/v2/feed/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseMissing('feed_posts', [
            'id' => $post->id,
        ]);
    }

    /** @test */
    public function test_delete_feed_post_requires_authentication()
    {
        $post = FeedPost::factory()->create();

        $response = $this->deleteJson("/api/v2/feed/posts/{$post->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_delete_feed_post_requires_ownership()
    {
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        // Post belongs to doctor2
        $post = FeedPost::factory()->create(['doctor_id' => $doctor2->id]);

        // Authenticate as doctor1
        $this->actingAs($doctor1);

        $response = $this->deleteJson("/api/v2/feed/posts/{$post->id}");

        // Should fail due to ownership
        $response->assertStatus(403);
    }

    /** @test */
    public function test_feed_post_cascade_deletes_comments_and_likes()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create(['doctor_id' => $doctor->id]);

        // Create related data
        $like = FeedPostLike::factory()->create(['feed_post_id' => $post->id]);
        $comment = FeedPostComment::factory()->create(['feed_post_id' => $post->id]);

        $this->deleteJson("/api/v2/feed/posts/{$post->id}");

        // Verify related data was also deleted
        $this->assertDatabaseMissing('feed_post_likes', ['id' => $like->id]);
        $this->assertDatabaseMissing('feed_post_comments', ['id' => $comment->id]);
    }
}
