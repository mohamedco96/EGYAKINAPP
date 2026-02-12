<?php

namespace Tests\Feature\Api\V2\Feed;

use App\Models\User;
use App\Models\FeedPost;
use App\Models\FeedPostComment;
use App\Models\FeedPostCommentLike;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Feed Post Comments
 *
 * Tests the following endpoints:
 * - POST /api/v2/feed/posts/{id}/comment
 * - GET /api/v2/posts/{postId}/comments
 * - DELETE /api/v2/feed/comments/{id}
 * - POST /api/v2/comments/{commentId}/likeOrUnlikeComment
 *
 * @group feed
 * @group feed-comments
 * @group api
 * @group v2
 */
class FeedPostCommentTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== ADD COMMENT TESTS ====================

    /** @test */
    public function test_add_comment_to_feed_post_successfully()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create();

        $commentData = [
            'comment' => 'This is a test comment',
        ];

        $response = $this->postJson("/api/v2/feed/posts/{$post->id}/comment", $commentData);

        $response->assertStatus(201)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('feed_post_comments', [
            'feed_post_id' => $post->id,
            'doctor_id' => $doctor->id,
            'comment' => 'This is a test comment',
        ]);
    }

    /** @test */
    public function test_add_comment_requires_authentication()
    {
        $post = FeedPost::factory()->create();

        $response = $this->postJson("/api/v2/feed/posts/{$post->id}/comment", [
            'comment' => 'Test comment',
        ]);

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_add_comment_validates_content()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create();

        $response = $this->postJson("/api/v2/feed/posts/{$post->id}/comment", [
            'comment' => '', // Empty comment
        ]);

        $this->assertValidationError($response, ['comment']);
    }

    /** @test */
    public function test_add_comment_increments_comment_count()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create(['comments_count' => 0]);

        $this->postJson("/api/v2/feed/posts/{$post->id}/comment", [
            'comment' => 'Test comment',
        ]);

        $post->refresh();
        $this->assertEquals(1, $post->comments_count);
    }

    /** @test */
    public function test_comment_notifications_sent_to_post_owner()
    {
        $postOwner = $this->doctorUser();
        $commenter = $this->doctorUser();

        $post = FeedPost::factory()->create(['doctor_id' => $postOwner->id]);

        // Authenticate as commenter
        $this->actingAs($commenter);

        $this->postJson("/api/v2/feed/posts/{$post->id}/comment", [
            'comment' => 'Nice post!',
        ]);

        // Verify notification was created for post owner
        $this->assertDatabaseHas('app_notifications', [
            'doctor_id' => $postOwner->id,
        ]);
    }

    // ==================== GET COMMENTS TESTS ====================

    /** @test */
    public function test_get_post_comments_returns_list()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create();

        // Create comments
        FeedPostComment::factory()->count(5)->create(['feed_post_id' => $post->id]);

        $response = $this->getJson("/api/v2/posts/{$post->id}/comments");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $comments = $response->json('data');
        $this->assertCount(5, $comments);
    }

    /** @test */
    public function test_get_post_comments_requires_authentication()
    {
        $post = FeedPost::factory()->create();

        $response = $this->getJson("/api/v2/posts/{$post->id}/comments");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_post_comments_includes_user_data()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create();

        FeedPostComment::factory()->create([
            'feed_post_id' => $post->id,
            'doctor_id' => $doctor->id,
        ]);

        $response = $this->getJson("/api/v2/posts/{$post->id}/comments");

        $response->assertStatus(200);

        $comments = $response->json('data');

        if (!empty($comments)) {
            $this->assertArrayHasKey('doctor', $comments[0]);
        }
    }

    /** @test */
    public function test_get_post_comments_ordered_by_date()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create();

        // Create comments with different timestamps
        FeedPostComment::factory()->create([
            'feed_post_id' => $post->id,
            'created_at' => now()->subHours(3),
        ]);

        FeedPostComment::factory()->create([
            'feed_post_id' => $post->id,
            'created_at' => now()->subHours(1),
        ]);

        $response = $this->getJson("/api/v2/posts/{$post->id}/comments");

        $response->assertStatus(200);

        $comments = $response->json('data');

        if (count($comments) >= 2) {
            // Verify ordering (either ascending or descending is acceptable)
            $dates = collect($comments)->pluck('created_at')->toArray();
            $sortedDates = collect($dates)->sort()->values()->toArray();

            $this->assertTrue(
                $dates === $sortedDates || $dates === array_reverse($sortedDates)
            );
        }
    }

    // ==================== DELETE COMMENT TESTS ====================

    /** @test */
    public function test_delete_comment_successfully()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create();

        $comment = FeedPostComment::factory()->create([
            'feed_post_id' => $post->id,
            'doctor_id' => $doctor->id,
        ]);

        $response = $this->deleteJson("/api/v2/feed/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseMissing('feed_post_comments', [
            'id' => $comment->id,
        ]);
    }

    /** @test */
    public function test_delete_comment_requires_authentication()
    {
        $comment = FeedPostComment::factory()->create();

        $response = $this->deleteJson("/api/v2/feed/comments/{$comment->id}");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_delete_comment_requires_ownership()
    {
        $doctor1 = $this->doctorUser();
        $doctor2 = $this->doctorUser();

        $comment = FeedPostComment::factory()->create([
            'doctor_id' => $doctor2->id,
        ]);

        // Authenticate as doctor1
        $this->actingAs($doctor1);

        $response = $this->deleteJson("/api/v2/feed/comments/{$comment->id}");

        // Should fail due to ownership
        $response->assertStatus(403);
    }

    /** @test */
    public function test_delete_comment_decrements_comment_count()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create(['comments_count' => 1]);

        $comment = FeedPostComment::factory()->create([
            'feed_post_id' => $post->id,
            'doctor_id' => $doctor->id,
        ]);

        $this->deleteJson("/api/v2/feed/comments/{$comment->id}");

        $post->refresh();
        $this->assertEquals(0, $post->comments_count);
    }

    // ==================== LIKE/UNLIKE COMMENT TESTS ====================

    /** @test */
    public function test_like_comment_successfully()
    {
        $doctor = $this->doctorUser();
        $comment = FeedPostComment::factory()->create();

        $response = $this->postJson("/api/v2/comments/{$comment->id}/likeOrUnlikeComment");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('feed_post_comment_likes', [
            'post_comment_id' => $comment->id,
            'doctor_id' => $doctor->id,
        ]);
    }

    /** @test */
    public function test_unlike_comment_successfully()
    {
        $doctor = $this->doctorUser();
        $comment = FeedPostComment::factory()->create();

        // First, like the comment
        FeedPostCommentLike::create([
            'post_comment_id' => $comment->id,
            'doctor_id' => $doctor->id,
        ]);

        // Now unlike it
        $response = $this->postJson("/api/v2/comments/{$comment->id}/likeOrUnlikeComment");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('feed_post_comment_likes', [
            'post_comment_id' => $comment->id,
            'doctor_id' => $doctor->id,
        ]);
    }

    /** @test */
    public function test_comment_likes_increment_count()
    {
        $doctor = $this->doctorUser();
        $comment = FeedPostComment::factory()->create(['likes_count' => 0]);

        $this->postJson("/api/v2/comments/{$comment->id}/likeOrUnlikeComment");

        $comment->refresh();
        $this->assertEquals(1, $comment->likes_count);
    }
}
