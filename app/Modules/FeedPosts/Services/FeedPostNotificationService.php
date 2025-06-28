<?php

namespace App\Modules\FeedPosts\Services;

use App\Models\FeedPost;
use App\Models\User;
use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FeedPostNotificationService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Notify doctors about new post creation
     */
    public function notifyDoctors(FeedPost $post): array
    {
        try {
            $doctors = User::where('id', '!=', Auth::id())
                ->where('isSyndicateCardRequired', 'Verified')
                ->pluck('id'); 

            $user = Auth::user();
            $doctorName = $user->name . ' ' . $user->lname;

            // Create notifications for all doctors
            $notifications = $doctors->map(function ($doctorId) use ($post, $doctorName, $user) {
                return [
                    'doctor_id' => $doctorId,
                    'type' => 'Post',
                    'type_id' => $post->id,
                    'content' => sprintf('Dr. %s added a new post', $doctorName),
                    'type_doctor_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            })->toArray();

            $createdNotifications = AppNotification::insert($notifications);

            // Send FCM push notifications
            $title = 'New Post was created 📣';
            $body = 'Dr. ' . ucfirst($user->name) . ' added a new post ';
            $tokens = FcmToken::whereIn('doctor_id', $doctors)
                ->pluck('token')
                ->toArray();

            $this->notificationService->sendPushNotification($title, $body, $tokens);

            Log::info("Notifications inserted successfully for post ID: " . $post->id);

            return $notifications;
        } catch (\Exception $e) {
            Log::error('Notification creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create notification for post like
     */
    public function notifyPostLike(FeedPost $post, User $liker): void
    {
        try {
            $postOwner = User::select(['id', 'name', 'lname'])->find($post->doctor_id);

            if ($postOwner->id !== $liker->id) {
                AppNotification::create([
                    'doctor_id' => $postOwner->id,
                    'type' => 'PostLike',
                    'type_id' => $post->id,
                    'content' => sprintf('Dr. %s liked your post', $liker->name . ' ' . $liker->lname),
                    'type_doctor_id' => $liker->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Send FCM push notification
                $tokens = FcmToken::where('doctor_id', $postOwner->id)
                    ->pluck('token')
                    ->toArray();

                if (!empty($tokens)) {
                    $this->notificationService->sendPushNotification(
                        'Post was liked 📣',
                        'Dr. ' . ucfirst($liker->name) . ' liked your post',
                        $tokens
                    );
                }

                Log::info("Post like notification sent to owner ID: " . $postOwner->id);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create post like notification: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create notification for post comment
     */
    public function notifyPostComment(FeedPost $post, User $commenter): void
    {
        try {
            $postOwner = $post->doctor;

            if ($postOwner->id !== $commenter->id) {
                AppNotification::create([
                    'doctor_id' => $postOwner->id,
                    'type' => 'PostComment',
                    'type_id' => $post->id,
                    'content' => sprintf('Dr. %s commented on your post', $commenter->name . ' ' . $commenter->lname),
                    'type_doctor_id' => $commenter->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Send FCM push notification
                $tokens = FcmToken::where('doctor_id', $postOwner->id)
                    ->pluck('token')
                    ->toArray();

                if (!empty($tokens)) {
                    $this->notificationService->sendPushNotification(
                        'New Comment was added 📣',
                        'Dr. ' . ucfirst($commenter->name) . ' commented on your post',
                        $tokens
                    );
                }

                Log::info("Post comment notification sent to owner ID: " . $postOwner->id);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create post comment notification: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create notification for comment like
     */
    public function notifyCommentLike($comment, User $liker): void
    {
        try {
            $commentOwner = $comment->doctor;

            if ($commentOwner->id !== $liker->id) {
                AppNotification::create([
                    'doctor_id' => $commentOwner->id,
                    'type' => 'CommentLike',
                    'type_id' => $comment->feed_post_id,
                    'content' => sprintf('Dr. %s liked your comment', $liker->name . ' ' . $liker->lname),
                    'type_doctor_id' => $liker->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info("Comment like notification sent to owner ID: " . $commentOwner->id);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create comment like notification: ' . $e->getMessage());
            throw $e;
        }
    }
}