<?php

namespace App\Modules\FeedPosts\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FeedPosts\Services\FeedPostService;
use App\Modules\FeedPosts\Services\FeedPostLikeService;
use App\Modules\FeedPosts\Services\FeedPostSaveService;
use App\Modules\FeedPosts\Services\FeedPostCommentService;
use App\Modules\FeedPosts\Services\HashtagService;
use App\Modules\FeedPosts\Services\MediaUploadService;
use App\Modules\FeedPosts\Services\PollService;
use App\Modules\FeedPosts\Services\FeedPostNotificationService;
use App\Modules\FeedPosts\Requests\StoreFeedPostRequest;
use App\Modules\FeedPosts\Requests\UpdateFeedPostRequest;
use App\Modules\FeedPosts\Requests\CommentRequest;
use App\Modules\FeedPosts\Requests\LikePostRequest;
use App\Modules\FeedPosts\Requests\SavePostRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeedPostController extends Controller
{
    protected $feedPostService;
    protected $feedPostLikeService;
    protected $feedPostSaveService;
    protected $feedPostCommentService;
    protected $hashtagService;
    protected $mediaUploadService;
    protected $pollService;
    protected $notificationService;

    public function __construct(
        FeedPostService $feedPostService,
        FeedPostLikeService $feedPostLikeService,
        FeedPostSaveService $feedPostSaveService,
        FeedPostCommentService $feedPostCommentService,
        HashtagService $hashtagService,
        MediaUploadService $mediaUploadService,
        PollService $pollService,
        FeedPostNotificationService $notificationService
    ) {
        $this->feedPostService = $feedPostService;
        $this->feedPostLikeService = $feedPostLikeService;
        $this->feedPostSaveService = $feedPostSaveService;
        $this->feedPostCommentService = $feedPostCommentService;
        $this->hashtagService = $hashtagService;
        $this->mediaUploadService = $mediaUploadService;
        $this->pollService = $pollService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of feed posts
     */
    public function index(): JsonResponse
    {
        $result = $this->feedPostService->getAllPosts();
        $statusCode = $result['value'] ? 200 : 404;
        return response()->json($result, $statusCode);
    }

    /**
     * Get feed posts for authenticated user
     */
    public function getFeedPosts(): JsonResponse
    {
        $result = $this->feedPostService->getFeedPosts();
        $statusCode = $result['value'] ? 200 : 500;
        return response()->json($result, $statusCode);
    }

    /**
     * Get posts by doctor ID
     */
    public function getDoctorPosts(int $doctorId): JsonResponse
    {
        $result = $this->feedPostService->getDoctorPosts($doctorId);
        $statusCode = $result['value'] ? 200 : 500;
        return response()->json($result, $statusCode);
    }

    /**
     * Get saved posts for doctor
     */
    public function getDoctorSavedPosts(?int $doctorId = null): JsonResponse
    {
        $result = $this->feedPostService->getDoctorSavedPosts($doctorId);
        $statusCode = $result['value'] ? 200 : 400;
        return response()->json($result, $statusCode);
    }

    /**
     * Get post by ID
     */
    public function getPostById(int $id): JsonResponse
    {
        $result = $this->feedPostService->getPostById($id);
        $statusCode = $result['value'] ? 200 : ($result['message'] === 'Post not found' ? 404 : 500);
        return response()->json($result, $statusCode);
    }

    /**
     * Store a newly created feed post
     */
    public function store(StoreFeedPostRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validated();

            // Validate group access
            $this->feedPostService->validateGroupAccess($validatedData['group_id'] ?? null);

            // Handle media upload
            $mediaPaths = [];
            if ($request->hasFile('media_path')) {
                $mediaType = $this->mediaUploadService->validateMediaType($request);
                $mediaPaths = $this->mediaUploadService->handleMediaUpload($request, $mediaType);
            }

            // Create post
            $postResult = $this->feedPostService->createPost($validatedData, $mediaPaths);
            $post = $postResult['post'];

            // Handle hashtags
            if (!empty($validatedData['content'])) {
                $this->hashtagService->attachHashtags($post, $validatedData['content']);
            }

            // Handle poll creation
            if (isset($validatedData['poll']) && $this->pollService->validatePollData($validatedData['poll'])) {
                $this->pollService->createPoll($post, $validatedData['poll']);
            }

            DB::commit();

            // Send notifications (non-blocking)
            try {
                $this->notificationService->notifyDoctors($post);
            } catch (\Exception $e) {
                Log::error('Notification creation failed: ' . $e->getMessage());
            }

            return response()->json([
                'value' => true,
                'data' => $post->load('poll.options'),
                'message' => 'Post created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating post: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => "Error creating post: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified feed post
     */
    public function update(UpdateFeedPostRequest $request, int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validated();

            // Validate group access
            $this->feedPostService->validateGroupAccess($validatedData['group_id'] ?? null);

            // Handle media updates
            $finalMediaPaths = [];
            if ($request->has('existing_media_path')) {
                $finalMediaPaths = array_filter($request->input('existing_media_path', []));
            }

            if ($request->hasFile('media_path')) {
                $newMediaPaths = $this->mediaUploadService->uploadMultipleImages($request->file('media_path'));
                $finalMediaPaths = array_merge($finalMediaPaths, $newMediaPaths);
            }

            // Update post
            $postResult = $this->feedPostService->updatePost($id, $validatedData, $finalMediaPaths);
            $post = $postResult['post'];

            // Handle hashtag updates if content changed
            if ($post->wasChanged('content')) {
                $this->hashtagService->detachHashtags($post);
                if (!empty($validatedData['content'])) {
                    $this->hashtagService->attachHashtags($post, $validatedData['content']);
                }
            }

            // Handle poll updates
            if (isset($validatedData['poll']) && $this->pollService->validatePollData($validatedData['poll'])) {
                $this->pollService->updatePoll($post, $validatedData['poll']);
            }

            DB::commit();

            return response()->json([
                'value' => true,
                'data' => $post->load('poll.options'),
                'message' => 'Post updated successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating post ID $id: " . $e->getMessage());
            
            $statusCode = str_contains($e->getMessage(), 'Unauthorized') ? 403 : 
                         (str_contains($e->getMessage(), 'not found') ? 404 : 500);
            
            return response()->json([
                'value' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Remove the specified feed post
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->feedPostService->deletePost($id);
        
        $statusCode = $result['value'] ? 200 : 
                     ($result['message'] === 'Post not found' ? 404 : 
                     ($result['message'] === 'Unauthorized action' ? 403 : 500));
        
        return response()->json($result, $statusCode);
    }

    /**
     * Get likes for a post
     */
    public function getPostLikes(int $postId): JsonResponse
    {
        $result = $this->feedPostLikeService->getPostLikes($postId);
        $statusCode = $result['value'] ? 200 : 500;
        return response()->json($result, $statusCode);
    }

    /**
     * Like or unlike a post
     */
    public function likeOrUnlikePost(LikePostRequest $request, int $postId): JsonResponse
    {
        $validatedData = $request->validated();
        $result = $this->feedPostLikeService->likeOrUnlikePost($postId, $validatedData['status']);
        
        $statusCode = $result['value'] ? 200 : 
                     ($result['message'] === 'Post not found or not accessible' ? 404 : 
                     ($result['message'] === 'Post already liked' ? 400 : 500));
        
        return response()->json($result, $statusCode);
    }

    /**
     * Save or unsave a post
     */
    public function saveOrUnsavePost(SavePostRequest $request, int $postId): JsonResponse
    {
        $validatedData = $request->validated();
        $result = $this->feedPostSaveService->saveOrUnsavePost($postId, $validatedData['status']);
        
        $statusCode = $result['value'] ? 200 : 
                     ($result['message'] === 'Post not found' ? 404 : 
                     ($result['message'] === 'Post already saved' ? 400 : 500));
        
        return response()->json($result, $statusCode);
    }

    /**
     * Get comments for a post
     */
    public function getPostComments(int $postId): JsonResponse
    {
        $result = $this->feedPostCommentService->getPostComments($postId);
        $statusCode = $result['value'] ? 200 : 500;
        return response()->json($result, $statusCode);
    }

    /**
     * Add a comment to a post
     */
    public function addComment(CommentRequest $request, int $postId): JsonResponse
    {
        $validatedData = $request->validated();
        $result = $this->feedPostCommentService->addComment($postId, $validatedData);
        $statusCode = $result['value'] ? 200 : 500;
        return response()->json($result, $statusCode);
    }

    /**
     * Delete a comment
     */
    public function deleteComment(int $commentId): JsonResponse
    {
        $result = $this->feedPostCommentService->deleteComment($commentId);
        
        $statusCode = $result['value'] ? 200 : 
                     ($result['message'] === 'Comment not found' ? 404 : 
                     ($result['message'] === 'Unauthorized action' ? 403 : 500));
        
        return response()->json($result, $statusCode);
    }

    /**
     * Like or unlike a comment
     */
    public function likeOrUnlikeComment(LikePostRequest $request, int $commentId): JsonResponse
    {
        $validatedData = $request->validated();
        $result = $this->feedPostCommentService->likeOrUnlikeComment($commentId, $validatedData['status']);
        
        $statusCode = $result['value'] ? 200 : 
                     ($result['message'] === 'Comment not found' ? 404 : 
                     ($result['message'] === 'Comment already liked' ? 400 : 500));
        
        return response()->json($result, $statusCode);
    }

    /**
     * Get trending hashtags
     */
    public function trending(): JsonResponse
    {
        $result = $this->hashtagService->getTrendingHashtags();
        return response()->json($result, 200);
    }

    /**
     * Search hashtags
     */
    public function searchHashtags(Request $request): JsonResponse
    {
        $query = $request->input('query', '');
        $result = $this->hashtagService->searchHashtags($query);
        $statusCode = $result['value'] ? 200 : 400;
        return response()->json($result, $statusCode);
    }

    /**
     * Get posts by hashtag
     */
    public function getPostsByHashtag(int $hashtagId): JsonResponse
    {
        $result = $this->hashtagService->getPostsByHashtag($hashtagId);
        $statusCode = $result['value'] ? 200 : 500;
        return response()->json($result, $statusCode);
    }

    /**
     * Search posts
     */
    public function searchPosts(Request $request): JsonResponse
    {
        $query = $request->input('query', '');
        $result = $this->feedPostService->searchPosts($query);
        $statusCode = $result['value'] ? 200 : 500;
        return response()->json($result, $statusCode);
    }
}