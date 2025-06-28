<?php

namespace App\Modules\FeedPosts\Services;

use App\Models\FeedPostLike;
use App\Models\FeedPost;
use App\Models\User;
use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeedPostLikeService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get likes for a post
     */
    public function getPostLikes(int $postId): array
    {
        try {
            $likes = FeedPostLike::where('feed_post_id', $postId)
                ->with(['doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired'])
                ->paginate(10);

            if ($likes->isEmpty()) {
                Log::info("No likes found for post ID $postId");
                return [
                    'value' => false,
                    'data' => [],
                    'message' => 'No likes found for this post'
                ];
            }

            $doctorData = $likes->getCollection()->map(function ($like) {
                return $like->doctor;
            });

            $paginatedDoctorData = new \Illuminate\Pagination\LengthAwarePaginator(
                $doctorData,
                $likes->total(),
                $likes->perPage(),
                $likes->currentPage(),
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );

            Log::info("Likes retrieved for post ID $postId");
            return [
                'value' => true,
                'data' => $paginatedDoctorData,
                'message' => 'Post likes retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching likes for post ID $postId: " . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving post likes'
            ];
        }
    }

    /**
     * Like or unlike a post
     */
    public function likeOrUnlikePost(int $postId, string $status): array
    {
        try {
            $doctor_id = Auth::id();

            // Check if the post exists and is accessible
            $post = FeedPost::select(['id', 'doctor_id'])
                ->where('id', $postId)
                ->where(function ($query) {
                    $query->where('visibility', 'Public')
                        ->orWhere('doctor_id', Auth::id());
                })
                ->firstOrFail();

            // Find if the like already exists
            $like = FeedPostLike::where('feed_post_id', $postId)
                ->where('doctor_id', $doctor_id)
                ->first();

            // Handle Like
            if ($status === 'like') {
                if ($like) {
                    Log::warning("Post already liked PostID: $postId UserID: $doctor_id");
                    return [
                        'value' => false,
                        'message' => 'Post already liked'
                    ];
                }

                DB::beginTransaction();
                try {
                    // Create a new like entry
                    $newLike = FeedPostLike::create([
                        'feed_post_id' => $postId,
                        'doctor_id' => $doctor_id,
                    ]);

                    $postOwner = User::select(['id', 'name', 'lname'])
                        ->find($post->doctor_id);

                    // Create notification for post owner if not the same user
                    if ($postOwner->id !== Auth::id()) {
                        AppNotification::create([
                            'doctor_id' => $postOwner->id,
                            'type' => 'PostLike',
                            'type_id' => $post->id,
                            'content' => sprintf('Dr. %s liked your post', Auth::user()->name . ' ' . Auth::user()->lname),
                            'type_doctor_id' => Auth::id(),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        // Get FCM tokens for push notification
                        $tokens = FcmToken::where('doctor_id', $postOwner->id)
                            ->pluck('token')
                            ->toArray();

                        if (!empty($tokens)) {
                            $this->notificationService->sendPushNotification(
                                'Post was liked 📣',
                                'Dr. ' . ucfirst(Auth::user()->name) . ' liked your post',
                                $tokens
                            );
                        }
                    }

                    DB::commit();
                    Log::info("Post ID $postId liked by doctor " . $doctor_id);

                    return [
                        'value' => true,
                        'data' => $newLike,
                        'message' => 'Post liked successfully'
                    ];
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }

            // Handle Unlike
            } elseif ($status === 'unlike') {
                if ($like) {
                    $like->delete();
                    Log::info("Post ID $postId unliked by doctor " . $doctor_id);
                    return [
                        'value' => true,
                        'message' => 'Post unliked successfully'
                    ];
                }

                Log::warning("Like not found for post ID $postId");
                return [
                    'value' => false,
                    'message' => 'Like not found'
                ];
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $postId not found for like/unlike");
            return [
                'value' => false,
                'message' => 'Post not found or not accessible'
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning("Invalid input for like/unlike: " . json_encode($e->errors()));
            return [
                'value' => false,
                'message' => 'Invalid input. Status must be "like" or "unlike".'
            ];
        } catch (\Exception $e) {
            Log::error("Error processing like/unlike for post ID $postId: " . $e->getMessage());
            return [
                'value' => false,
                'message' => 'An error occurred while processing the request'
            ];
        }
    }
}
