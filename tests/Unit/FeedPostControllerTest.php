<?php

namespace Tests\Unit;

use App\Http\Controllers\FeedPostController;
use App\Http\Controllers\MainController;
use App\Models\FeedPost;
use App\Models\FeedPostComment;
use App\Models\FeedPostLike;
use App\Models\Hashtag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FeedPostControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $mainControllerMock;

    protected $feedPostController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mainControllerMock = $this->createMock(MainController::class);
        $this->feedPostController = new FeedPostController($this->mainControllerMock);
    }

    public function testExtractHashtags()
    {
        $content = 'This is a #test post with #multiple #hashtags.';
        $expected = ['test', 'multiple', 'hashtags'];

        $result = $this->feedPostController->extractHashtags($content);

        $this->assertEquals($expected, $result);
    }

    public function testExtractArabicHashtags()
    {
        $content = 'هاشتاج #بالعربي و #test و #مختلط123';
        $expected = ['بالعربي', 'test', 'مختلط123'];

        $result = $this->feedPostController->extractHashtags($content);

        $this->assertEquals($expected, $result);
    }

    public function testIndex()
    {
        FeedPost::factory()->count(5)->create();

        $response = $this->feedPostController->index();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Feed posts retrieved successfully', $response->getContent());
    }

    public function testShow()
    {
        $post = FeedPost::factory()->create();

        $response = $this->feedPostController->show($post->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Post retrieved successfully', $response->getContent());
    }

    public function testShowNotFound()
    {
        $response = $this->feedPostController->show(999);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Post not found', $response->getContent());
    }

    public function testGetFeedPosts()
    {
        $this->actingAs(FeedPost::factory()->create()->doctor);

        $response = $this->feedPostController->getFeedPosts();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Feed posts retrieved successfully', $response->getContent());
    }

    public function testGetPostById()
    {
        $post = FeedPost::factory()->create();

        $response = $this->feedPostController->getPostById($post->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Post retrieved successfully', $response->getContent());
    }

    public function testGetPostLikes()
    {
        $post = FeedPost::factory()->create();
        FeedPostLike::factory()->count(5)->create(['feed_post_id' => $post->id]);

        $response = $this->feedPostController->getPostLikes($post->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Post likes retrieved successfully', $response->getContent());
    }

    public function testGetPostComments()
    {
        $post = FeedPost::factory()->create();
        FeedPostComment::factory()->count(5)->create(['feed_post_id' => $post->id]);

        $response = $this->feedPostController->getPostComments($post->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Post comments retrieved successfully', $response->getContent());
    }

    public function testStore()
    {
        $this->actingAs(FeedPost::factory()->create()->doctor);

        $request = Request::create('/store', 'POST', [
            'content' => 'This is a test post',
            'media_type' => 'image',
            'visibility' => 'Public',
        ]);

        $response = $this->feedPostController->store($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Post created successfully', $response->getContent());
    }

    public function testDestroy()
    {
        $post = FeedPost::factory()->create();
        $this->actingAs($post->doctor);

        $response = $this->feedPostController->destroy($post->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Post deleted successfully', $response->getContent());
    }

    public function testUpdate()
    {
        $post = FeedPost::factory()->create();
        $this->actingAs($post->doctor);

        $request = Request::create('/update', 'PUT', [
            'content' => 'Updated content',
            'visibility' => 'Friends',
        ]);

        $response = $this->feedPostController->update($request, $post->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Post updated successfully', $response->getContent());
    }

    public function testLikeOrUnlikePost()
    {
        $post = FeedPost::factory()->create();
        $this->actingAs($post->doctor);

        $request = Request::create('/like', 'POST', ['status' => 'like']);
        $response = $this->feedPostController->likeOrUnlikePost($request, $post->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Post liked successfully', $response->getContent());

        $request = Request::create('/unlike', 'POST', ['status' => 'unlike']);
        $response = $this->feedPostController->likeOrUnlikePost($request, $post->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Post unliked successfully', $response->getContent());
    }

    public function testSaveOrUnsavePost()
    {
        $post = FeedPost::factory()->create();
        $this->actingAs($post->doctor);

        $request = Request::create('/save', 'POST', ['status' => 'save']);
        $response = $this->feedPostController->saveOrUnsavePost($request, $post->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Post saved successfully', $response->getContent());

        $request = Request::create('/unsave', 'POST', ['status' => 'unsave']);
        $response = $this->feedPostController->saveOrUnsavePost($request, $post->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Post unsaved successfully', $response->getContent());
    }

    public function testAddComment()
    {
        $post = FeedPost::factory()->create();
        $this->actingAs($post->doctor);

        $request = Request::create('/comment', 'POST', [
            'comment' => 'This is a test comment',
        ]);

        $response = $this->feedPostController->addComment($request, $post->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Comment added successfully', $response->getContent());
    }

    public function testDeleteComment()
    {
        $comment = FeedPostComment::factory()->create();
        $this->actingAs($comment->doctor);

        $response = $this->feedPostController->deleteComment($comment->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Comment deleted successfully', $response->getContent());
    }

    public function testLikeOrUnlikeComment()
    {
        $comment = FeedPostComment::factory()->create();
        $this->actingAs($comment->doctor);

        $request = Request::create('/like', 'POST', ['status' => 'like']);
        $response = $this->feedPostController->likeOrUnlikeComment($request, $comment->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Comment liked successfully', $response->getContent());

        $request = Request::create('/unlike', 'POST', ['status' => 'unlike']);
        $response = $this->feedPostController->likeOrUnlikeComment($request, $comment->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Comment unliked successfully', $response->getContent());
    }

    public function testTrending()
    {
        Hashtag::factory()->count(10)->create();

        $response = $this->feedPostController->trending();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }
}
