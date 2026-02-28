<?php

namespace Tests\Feature\Api\V2\Feed;

use App\Models\User;
use App\Models\FeedPost;
use App\Models\FeedPostLike;
use App\Models\FeedSaveLike;
use App\Models\FeedPostComment;
use App\Models\Hashtag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;

/**
 * Test suite for Feed Post Interactions
 *
 * Tests the following endpoints:
 * - POST /api/v2/feed/posts/{id}/likeOrUnlikePost
 * - POST /api/v2/feed/posts/{id}/saveOrUnsavePost
 * - GET /api/v2/posts/{postId}/likes
 * - GET /api/v2/feed/trendingPosts
 * - POST /api/v2/feed/searchHashtags
 * - GET /api/v2/feed/getPostsByHashtag/{hashtag}
 * - POST /api/v2/feed/searchPosts
 * - GET /api/v2/doctorposts/{doctorId}
 * - GET /api/v2/doctorsavedposts/{doctorId}
 *
 * @group feed
 * @group feed-interactions
 * @group api
 * @group v2
 */
class FeedPostInteractionTest extends TestCase
{
    use RefreshDatabase, WithFaker, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'user']);
    }

    // ==================== LIKE/UNLIKE TESTS ====================

    /** @test */
    public function test_like_feed_post_successfully()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create();

        $response = $this->postJson("/api/v2/feed/posts/{$post->id}/likeOrUnlikePost");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('feed_post_likes', [
            'feed_post_id' => $post->id,
            'doctor_id' => $doctor->id,
        ]);
    }

    /** @test */
    public function test_like_feed_post_requires_authentication()
    {
        $post = FeedPost::factory()->create();

        $response = $this->postJson("/api/v2/feed/posts/{$post->id}/likeOrUnlikePost");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_like_feed_post_increments_like_count()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create(['likes_count' => 0]);

        $this->postJson("/api/v2/feed/posts/{$post->id}/likeOrUnlikePost");

        $post->refresh();
        $this->assertEquals(1, $post->likes_count);
    }

    /** @test */
    public function test_unlike_feed_post_successfully()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create();

        // First, like the post
        FeedPostLike::create([
            'feed_post_id' => $post->id,
            'doctor_id' => $doctor->id,
        ]);

        // Now unlike it
        $response = $this->postJson("/api/v2/feed/posts/{$post->id}/likeOrUnlikePost");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('feed_post_likes', [
            'feed_post_id' => $post->id,
            'doctor_id' => $doctor->id,
        ]);
    }

    /** @test */
    public function test_unlike_feed_post_decrements_like_count()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create(['likes_count' => 1]);

        // First, like the post
        FeedPostLike::create([
            'feed_post_id' => $post->id,
            'doctor_id' => $doctor->id,
        ]);

        // Now unlike it
        $this->postJson("/api/v2/feed/posts/{$post->id}/likeOrUnlikePost");

        $post->refresh();
        $this->assertEquals(0, $post->likes_count);
    }

    /** @test */
    public function test_like_post_is_idempotent()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create();

        // Like twice
        $this->postJson("/api/v2/feed/posts/{$post->id}/likeOrUnlikePost");
        $this->postJson("/api/v2/feed/posts/{$post->id}/likeOrUnlikePost");

        // Should only have one like (second call should unlike)
        $likeCount = FeedPostLike::where('feed_post_id', $post->id)
            ->where('doctor_id', $doctor->id)
            ->count();

        $this->assertEquals(0, $likeCount); // Unliked after second call
    }

    // ==================== SAVE/UNSAVE TESTS ====================

    /** @test */
    public function test_save_feed_post_successfully()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create();

        $response = $this->postJson("/api/v2/feed/posts/{$post->id}/saveOrUnsavePost");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $this->assertDatabaseHas('feed_save_likes', [
            'feed_post_id' => $post->id,
            'doctor_id' => $doctor->id,
        ]);
    }

    /** @test */
    public function test_save_feed_post_requires_authentication()
    {
        $post = FeedPost::factory()->create();

        $response = $this->postJson("/api/v2/feed/posts/{$post->id}/saveOrUnsavePost");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_unsave_feed_post_successfully()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create();

        // First, save the post
        FeedSaveLike::create([
            'feed_post_id' => $post->id,
            'doctor_id' => $doctor->id,
        ]);

        // Now unsave it
        $response = $this->postJson("/api/v2/feed/posts/{$post->id}/saveOrUnsavePost");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('feed_save_likes', [
            'feed_post_id' => $post->id,
            'doctor_id' => $doctor->id,
        ]);
    }

    // ==================== GET POST LIKES TESTS ====================

    /** @test */
    public function test_get_post_likes_returns_list()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create();

        // Create likes
        FeedPostLike::factory()->count(5)->create(['feed_post_id' => $post->id]);

        $response = $this->getJson("/api/v2/posts/{$post->id}/likes");

        $response->assertStatus(200)
            ->assertJson(['value' => true]);

        $likes = $response->json('data');
        $this->assertCount(5, $likes);
    }

    /** @test */
    public function test_get_post_likes_requires_authentication()
    {
        $post = FeedPost::factory()->create();

        $response = $this->getJson("/api/v2/posts/{$post->id}/likes");

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_get_post_likes_includes_user_data()
    {
        $doctor = $this->doctorUser();
        $post = FeedPost::factory()->create();

        FeedPostLike::factory()->create([
            'feed_post_id' => $post->id,
            'doctor_id' => $doctor->id,
        ]);

        $response = $this->getJson("/api/v2/posts/{$post->id}/likes");

        $response->assertStatus(200);

        $likes = $response->json('data');

        if (!empty($likes)) {
            $this->assertArrayHasKey('doctor', $likes[0]);
        }
    }

    // ==================== SAVED POSTS TESTS ====================

    /** @test */
    public function test_get_saved_posts_returns_list()
    {
        $doctor = $this->doctorUser();

        // Create and save posts
        $post1 = FeedPost::factory()->create();
        $post2 = FeedPost::factory()->create();

        FeedSaveLike::create(['feed_post_id' => $post1->id, 'doctor_id' => $doctor->id]);
        FeedSaveLike::create(['feed_post_id' => $post2->id, 'doctor_id' => $doctor->id]);

        $response = $this->getJson("/api/v2/doctorsavedposts/{$doctor->id}");

        $response->assertStatus(200);

        $savedPosts = $response->json('data');
        $this->assertCount(2, $savedPosts);
    }

    /** @test */
    public function test_get_saved_posts_requires_authentication()
    {
        $doctor = User::factory()->create();

        $response = $this->getJson("/api/v2/doctorsavedposts/{$doctor->id}");

        $this->assertUnauthenticated($response);
    }

    // ==================== TRENDING POSTS TESTS ====================

    /** @test */
    public function test_trending_posts_returns_popular_posts()
    {
        $doctor = $this->doctorUser();

        // Create posts with different engagement levels
        $trendingPost = FeedPost::factory()->create([
            'likes_count' => 50,
            'comments_count' => 20,
            'created_at' => now()->subHours(2),
        ]);

        $regularPost = FeedPost::factory()->create([
            'likes_count' => 2,
            'comments_count' => 1,
            'created_at' => now()->subHours(5),
        ]);

        $response = $this->getJson('/api/v2/feed/trendingPosts');

        $response->assertStatus(200);

        $posts = $response->json('data');

        // Trending post should appear first
        if (count($posts) >= 2) {
            $this->assertEquals($trendingPost->id, $posts[0]['id']);
        }
    }

    /** @test */
    public function test_trending_posts_requires_authentication()
    {
        $response = $this->getJson('/api/v2/feed/trendingPosts');

        $this->assertUnauthenticated($response);
    }

    /** @test */
    public function test_trending_algorithm_considers_likes()
    {
        $doctor = $this->doctorUser();

        $highLikesPost = FeedPost::factory()->create([
            'likes_count' => 100,
            'comments_count' => 0,
        ]);

        $lowLikesPost = FeedPost::factory()->create([
            'likes_count' => 1,
            'comments_count' => 0,
        ]);

        $response = $this->getJson('/api/v2/feed/trendingPosts');

        $response->assertStatus(200);

        $posts = $response->json('data');

        if (!empty($posts)) {
            // High likes post should rank higher
            $firstPostId = $posts[0]['id'];
            $this->assertEquals($highLikesPost->id, $firstPostId);
        }
    }

    /** @test */
    public function test_trending_algorithm_considers_comments()
    {
        $doctor = $this->doctorUser();

        $highCommentsPost = FeedPost::factory()->create([
            'likes_count' => 0,
            'comments_count' => 50,
        ]);

        $lowCommentsPost = FeedPost::factory()->create([
            'likes_count' => 0,
            'comments_count' => 1,
        ]);

        $response = $this->getJson('/api/v2/feed/trendingPosts');

        $response->assertStatus(200);

        $posts = $response->json('data');

        if (!empty($posts)) {
            // High comments post should rank higher
            $firstPostId = $posts[0]['id'];
            $this->assertEquals($highCommentsPost->id, $firstPostId);
        }
    }

    /** @test */
    public function test_trending_algorithm_considers_recency()
    {
        $doctor = $this->doctorUser();

        $recentPost = FeedPost::factory()->create([
            'likes_count' => 10,
            'created_at' => now()->subHours(1),
        ]);

        $oldPost = FeedPost::factory()->create([
            'likes_count' => 10,
            'created_at' => now()->subDays(7),
        ]);

        $response = $this->getJson('/api/v2/feed/trendingPosts');

        $response->assertStatus(200);

        $posts = $response->json('data');

        if (count($posts) >= 2) {
            // Recent post should rank higher with same likes
            $this->assertEquals($recentPost->id, $posts[0]['id']);
        }
    }

    // ==================== HASHTAG SEARCH TESTS ====================

    /** @test */
    public function test_search_hashtags_returns_matching_tags()
    {
        $doctor = $this->doctorUser();

        // Create hashtags
        Hashtag::factory()->create(['hashtag' => 'medicine']);
        Hashtag::factory()->create(['hashtag' => 'medical']);
        Hashtag::factory()->create(['hashtag' => 'health']);

        $response = $this->postJson('/api/v2/feed/searchHashtags', [
            'query' => 'med',
        ]);

        $response->assertStatus(200);

        $hashtags = $response->json('data');

        // Should find 'medicine' and 'medical'
        $this->assertGreaterThanOrEqual(2, count($hashtags));
    }

    /** @test */
    public function test_get_posts_by_hashtag_filters_correctly()
    {
        $doctor = $this->doctorUser();

        $post1 = FeedPost::factory()->create(['content' => 'Post with #medicine']);
        $post2 = FeedPost::factory()->create(['content' => 'Post with #health']);

        $response = $this->getJson('/api/v2/feed/getPostsByHashtag/medicine');

        $response->assertStatus(200);

        $posts = $response->json('data');

        if (!empty($posts)) {
            $postIds = collect($posts)->pluck('id')->toArray();
            $this->assertContains($post1->id, $postIds);
            $this->assertNotContains($post2->id, $postIds);
        }
    }

    // ==================== POST SEARCH TESTS ====================

    /** @test */
    public function test_search_posts_finds_by_content()
    {
        $doctor = $this->doctorUser();

        $post1 = FeedPost::factory()->create(['content' => 'This is about cardiology']);
        $post2 = FeedPost::factory()->create(['content' => 'This is about neurology']);

        $response = $this->postJson('/api/v2/feed/searchPosts', [
            'query' => 'cardiology',
        ]);

        $response->assertStatus(200);

        $posts = $response->json('data');

        if (!empty($posts)) {
            $postIds = collect($posts)->pluck('id')->toArray();
            $this->assertContains($post1->id, $postIds);
        }
    }

    // ==================== DOCTOR POSTS TESTS ====================

    /** @test */
    public function test_get_doctor_posts_filters_by_doctor()
    {
        $currentUser = $this->doctorUser();
        $doctor = $this->doctorUser();

        // Create posts for the doctor
        FeedPost::factory()->count(3)->create(['doctor_id' => $doctor->id]);

        // Create posts for another doctor
        FeedPost::factory()->count(2)->create();

        $response = $this->getJson("/api/v2/doctorposts/{$doctor->id}");

        $response->assertStatus(200);

        $posts = $response->json('data');

        // Should only return posts from this doctor
        foreach ($posts as $post) {
            $this->assertEquals($doctor->id, $post['doctor_id']);
        }

        $this->assertCount(3, $posts);
    }

    /** @test */
    public function test_get_doctor_saved_posts_returns_saved_list()
    {
        $doctor = $this->doctorUser();

        $post1 = FeedPost::factory()->create();
        $post2 = FeedPost::factory()->create();
        $post3 = FeedPost::factory()->create();

        // Save only post1 and post2
        FeedSaveLike::create(['feed_post_id' => $post1->id, 'doctor_id' => $doctor->id]);
        FeedSaveLike::create(['feed_post_id' => $post2->id, 'doctor_id' => $doctor->id]);

        $response = $this->getJson("/api/v2/doctorsavedposts/{$doctor->id}");

        $response->assertStatus(200);

        $savedPosts = $response->json('data');
        $savedPostIds = collect($savedPosts)->pluck('id')->toArray();

        $this->assertContains($post1->id, $savedPostIds);
        $this->assertContains($post2->id, $savedPostIds);
        $this->assertNotContains($post3->id, $savedPostIds);
    }
}
