<?php

namespace App\Http\Controllers;

use App\Models\FeedPost;
use App\Models\FeedPostComment;
use App\Models\FeedPostCommentLike;
use App\Models\FeedPostLike;
use App\Models\FeedSaveLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\MainController;
use App\Models\Hashtag;
use App\Models\Group;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\Poll;
use App\Models\PollOption; // If you have a separate PollOption model
use App\Models\PollVote;
use App\Http\Controllers\NotificationController;
use App\Models\FcmToken;
use App\Http\Requests\StoreFeedPostRequest;
use App\Http\Requests\UpdateFeedPostRequest;
use App\Services\FeedPostService;
use Illuminate\Http\Response;

class FeedPostController extends Controller
{
    protected $mainController;
    protected $notificationController;
    protected $feedPostService;

    public function __construct(MainController $mainController, NotificationController $notificationController, FeedPostService $feedPostService)
    {
        $this->mainController = $mainController;
        $this->notificationController = $notificationController;
        $this->feedPostService = $feedPostService;
    }

    // Helper function to extract hashtags from post content
    public function extractHashtags($content)
    {
        preg_match_all('/#(\w+)/', $content, $matches);
        return $matches[1]; // Return an array of hashtags
    }

    // Fetch all feed posts with proper response structure and logging
    public function index()
    {
        $result = $this->feedPostService->getFeedPosts();
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    // Fetch feed posts with likes, comments, and saved status for the authenticated doctor
    public function getFeedPosts()
    {
        $result = $this->feedPostService->getFeedPosts();
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function getDoctorPosts($doctorId)
    {
        $result = $this->feedPostService->getDoctorPosts($doctorId);
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function getDoctorSavedPosts($doctorId = null)
    {
        $result = $this->feedPostService->getDoctorSavedPosts($doctorId);
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    // Get post by ID with sorted comments, likes, and saved status
    public function getPostById($id)
    {
        $result = $this->feedPostService->getPostById($id);
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_NOT_FOUND);
    }

    // Get likes for a post
    public function getPostLikes($postId)
    {
        $result = $this->feedPostService->getPostLikes($postId);
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_NOT_FOUND);
    }

    // Get comments for a post
    public function getPostComments($postId)
    {
        $result = $this->feedPostService->getPostComments($postId);
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    // Create a new post
    public function store(StoreFeedPostRequest $request)
    {
        $validatedData = $request->validated();
        $mediaPaths = [];

        if ($request->hasFile('media_path')) {
            $mediaPaths = $this->feedPostService->uploadMultipleImages($request->file('media_path'));
        }

        $result = $this->feedPostService->store($validatedData, $mediaPaths);
        return response()->json($result, $result['value'] ? Response::HTTP_CREATED : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    // Update a post
    public function update(UpdateFeedPostRequest $request, $id)
    {
        $validatedData = $request->validated();
        $result = $this->feedPostService->update($id, $validatedData);
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    // Delete a post
    public function destroy($id)
    {
        $result = $this->feedPostService->destroy($id);
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function likeOrUnlikePost(Request $request, $postId)
    {
        $validatedData = $request->validate([
            'status' => 'required|string|in:like,unlike'
        ]);

        $result = $this->feedPostService->likeOrUnlikePost($postId, $validatedData['status']);
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function saveOrUnsavePost(Request $request, $postId)
    {
        $validatedData = $request->validate([
            'status' => 'required|string|in:save,unsave'
        ]);

        $result = $this->feedPostService->saveOrUnsavePost($postId, $validatedData['status']);
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    // Add a comment to a post
    public function addComment(Request $request, $postId)
    {
        $validatedData = $request->validate([
            'comment' => 'required|string|max:500',
            'parent_id' => 'nullable|exists:feed_post_comments,id',
        ]);

        $result = $this->feedPostService->addComment($postId, $validatedData);
        return response()->json($result, $result['value'] ? Response::HTTP_CREATED : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    // Delete a comment
    public function deleteComment($commentId)
    {
        $result = $this->feedPostService->deleteComment($commentId);
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function likeOrUnlikeComment(Request $request, $commentId)
    {
        $validatedData = $request->validate([
            'status' => 'required|string|in:like,unlike'
        ]);

        $result = $this->feedPostService->likeOrUnlikeComment($commentId, $validatedData['status']);
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function trending()
    {
        $result = $this->feedPostService->trending();
        return response()->json($result);
    }

    // Search for hashtags
    public function searchHashtags(Request $request)
    {
        $validatedData = $request->validate([
            'query' => 'required|string'
        ]);

        $result = $this->feedPostService->searchHashtags($validatedData['query']);
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    // Get posts by hashtag
    public function getPostsByHashtag($hashtagId)
    {
        $result = $this->feedPostService->getPostsByHashtag($hashtagId);
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    // Search for posts
    public function searchPosts(Request $request)
    {
        $validatedData = $request->validate([
            'query' => 'required|string'
        ]);

        $result = $this->feedPostService->searchPosts($validatedData['query']);
        return response()->json($result, $result['value'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
