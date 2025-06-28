<?php

namespace App\Modules\FeedPosts\Services;

use App\Models\FeedPost;
use App\Models\FeedPostComment;
use App\Models\FeedPostCommentLike;
use App\Models\User;
use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FeedPostCommentService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get comments for a post
     */
    public function getPostComments(int $postId): array
    {
        try {
            $doctorId = auth()->id();
            $postOwnerId = FeedPost::find($postId)->doctor_id;
            $perPage = 10;
            $page = request()->get('page', 1);

            // Fetch all comments with the necessary relations
            $comments = FeedPostComment::where('feed_post_id', $postId)
                ->whereNull('parent_id')
                ->with([
                    'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                    'replies' => function ($query) use ($doctorId) {
                        $query->with([
                            'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                            'likes' => function ($query) use ($doctorId) {
                                $query->where('doctor_id', $doctorId);
                            },
                            'replies' => function ($query) use ($doctorId) {
                                $query->withCount(['likes as likes_count', 'replies as replies_count'])
                                    ->with([
                                        'likes' => function ($query) use ($doctorId) {
                                            $query->where('doctor_id', $doctorId);
                                        }
                                    ]);
                            }
                        ])->withCount(['likes as likes_count', 'replies as replies_count']);
                    },
                    'likes' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId);
                    }
                ])
                ->withCount(['likes as likes_count', 'replies as replies_count'])
                ->get();

            // Reorder comments: Post owner's comments first, followed by auth user's comments
            $comments = $comments->sortByDesc(function ($comment) use ($doctorId, $postOwnerId) {
                return [
                    $comment->doctor_id === $postOwnerId ? 2 : 0,
                    $comment->doctor_id === $doctorId ? 1 : 0
                ];
            })->values();

            // Manually paginate the sorted comments
            $paginatedComments = $comments->forPage($page, $perPage);

            // Add 'isLiked' property for comments and replies
            foreach ($paginatedComments as $comment) {
                $comment->isLiked = $comment->likes->isNotEmpty();

                foreach ($comment->replies as $reply) {
                    $reply->isLiked = $reply->likes->isNotEmpty();
                    
                    foreach ($reply->replies as $nestedReply) {
                        $nestedReply->isLiked = $nestedReply->likes->isNotEmpty();
                    }
                }

                // Clean up likes relation data
                unset($comment->likes);
                foreach ($comment->replies as $reply) {
                    unset($reply->likes);
                    foreach ($reply->replies as $nestedReply) {
                        unset($nestedReply->likes);
                    }
                }
            }

            // Create a new paginator instance with the transformed data
            $paginatedResponse = new \Illuminate\Pagination\LengthAwarePaginator(
                $paginatedComments->values(),
                $comments->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            Log::info("Comments retrieved for post ID $postId");
            return [
                'value' => true,
                'data' => $paginatedResponse,
                'message' => 'Post comments retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching comments for post ID $postId: " . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving post comments'
            ];
        }
    }

    /**
     * Add a comment to a post
     */
    public function addComment(int $postId, array $validatedData): array
    {
        try {
            $comment = FeedPostComment::create([
                'feed_post_id' => $postId,
                'doctor_id' => Auth::id(),
                'comment' => $validatedData['comment'],
                'parent_id' => $validatedData['parent_id'] ?? null,
            ]);

            Log::info("Comment added to post ID $postId by doctor " . Auth::id());

            $post = FeedPost::findOrFail($postId);
            $postOwner = $post->doctor;

            // Create notification for post owner if not the same user
            if ($postOwner->id !== Auth::id()) {
                AppNotification::create([
                    'doctor_id' => $postOwner->id,
                    'type' => 'PostComment',
                    'type_id' => $post->id,
                    'content' => sprintf('Dr. %s commented on your post', Auth::user()->name . ' ' . Auth::user()->lname),
                    'type_doctor_id' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info("Notification sent to post owner ID: " . $postOwner->id . " for post ID: " . $post->id);

                // Get FCM tokens for push notification
                $tokens = FcmToken::where('doctor_id', $postOwner->id)
                    ->pluck('token')
                    ->toArray();

                if (!empty($tokens)) {
                    $this->notificationService->sendPushNotification(
                        'New Comment was added 📣',
                        'Dr. ' . ucfirst(Auth::user()->name) . ' commented on your post ',
                        $tokens
                    );
                }
            }

            return [
                'value' => true,
                'data' => $comment,
                'message' => 'Comment added successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error adding comment to post ID $postId: " . $e->getMessage());
            return [
                'value' => false,
                'message' => 'An error occurred while adding the comment'
            ];
        }
    }

    /**
     * Delete a comment
     */
    public function deleteComment(int $commentId): array
    {
        try {
            $comment = FeedPostComment::findOrFail($commentId);

            $user = Auth::user();
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

            // Allow only the comment owner or Admin/Tester
            if ($comment->doctor_id !== $user->id && !$isAdminOrTester) {
                Log::warning("Unauthorized comment deletion attempt by doctor " . Auth::id());
                return [
                    'value' => false,
                    'message' => 'Unauthorized action'
                ];
            }

            $comment->delete();
            Log::info("Comment ID $commentId deleted by doctor " . Auth::id());

            return [
                'value' => true,
                'message' => 'Comment deleted successfully'
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Comment ID $commentId not found for deletion");
            return [
                'value' => false,
                'message' => 'Comment not found'
            ];
        } catch (\Exception $e) {
            Log::error("Error deleting comment ID $commentId: " . $e->getMessage());
            return [
                'value' => false,
                'message' => 'An error occurred while deleting the comment'
            ];
        }
    }

    /**
     * Like or unlike a comment
     */
    public function likeOrUnlikeComment(int $commentId, string $status): array
    {
        try {
            $doctor_id = Auth::id();

            // Find if the comment exists
            $comment = FeedPostComment::find($commentId);

            if (!$comment) {
                Log::error("No comment was found with ID: $commentId");
                return [
                    'value' => false,
                    'message' => 'No comment was found with ID: ' . $commentId
                ];
            }

            // Find if the comment is already liked
            $like = FeedPostCommentLike::where('post_comment_id', $commentId)
                ->where('doctor_id', $doctor_id)
                ->first();

            // Handle Like
            if ($status === 'like') {
                if ($like) {
                    Log::warning("Comment already liked CommentID: $commentId UserID: $doctor_id");
                    return [
                        'value' => false,
                        'message' => 'Comment already liked'
                    ];
                }

                // Create a new like entry
                $newLike = FeedPostCommentLike::create([
                    'post_comment_id' => $commentId,
                    'doctor_id' => $doctor_id,
                ]);

                Log::info("Comment ID $commentId liked by doctor $doctor_id");

                $comment = FeedPostComment::findOrFail($commentId);
                $commentOwner = $comment->doctor;

                if ($commentOwner->id !== Auth::id()) {
                    AppNotification::create([
                        'doctor_id' => $commentOwner->id,
                        'type' => 'CommentLike',
                        'type_id' => $comment->feed_post_id,
                        'content' => sprintf('Dr. %s liked your comment', Auth::user()->name . ' ' . Auth::user()->lname),
                        'type_doctor_id' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    Log::info("Notification sent to comment owner ID: " . $commentOwner->id . " for comment ID: " . $comment->id);
                }

                return [
                    'value' => true,
                    'data' => $newLike,
                    'message' => 'Comment liked successfully'
                ];

            // Handle Unlike
            } elseif ($status === 'unlike') {
                if ($like) {
                    $like->delete();
                    Log::info("Comment ID $commentId unliked by doctor " . $doctor_id);
                    return [
                        'value' => true,
                        'message' => 'Comment unliked successfully'
                    ];
                }

                Log::warning("Like not found for comment ID $commentId");
                return [
                    'value' => false,
                    'message' => 'Like not found'
                ];
            } else {
                Log::warning("Invalid status for comment like/unlike: $status");
                return [
                    'value' => false,
                    'message' => 'Invalid status. Use "like" or "unlike".'
                ];
            }
        } catch (\Exception $e) {
            Log::error("Error processing like/unlike for comment ID $commentId: " . $e->getMessage());
            return [
                'value' => false,
                'message' => 'An error occurred while processing the request'
            ];
        }
    }
}
