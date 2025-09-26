<?php

namespace App\Modules\Posts\Services;

use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Posts\Models\PostComments;
use App\Modules\Posts\Models\Posts;
use App\Services\NotificationService;
use App\Traits\FormatsUserName;
use App\Traits\NotificationCleanup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PostCommentService
{
    use FormatsUserName, NotificationCleanup;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all comments for a specific post
     */
    public function getCommentsByPostId(int $postId): array
    {
        $postComments = PostComments::where('post_id', $postId)
            ->select('id', 'content', 'doctor_id', 'updated_at')
            ->with('doctor:id,name,lname,workingplace,image')
            ->get();

        return [
            'value' => true,
            'data' => $postComments,
        ];
    }

    /**
     * Create a new comment for a post
     */
    public function createComment(array $validatedData, int $postId): array
    {
        $post = Posts::find($postId);

        if (! $post) {
            return [
                'value' => false,
                'message' => 'No post was found',
            ];
        }

        $comment = new PostComments([
            'content' => $validatedData['content'],
        ]);

        $user = Auth::user();

        // Associate the comment with the current user and post
        $user->postcomments()->save($comment);
        $post->postcomments()->save($comment);

        // Send notification to post owner if different from commenter
        $this->sendCommentNotification($post, $comment, $user);

        return [
            'value' => true,
            'message' => 'Comment Created Successfully',
        ];
    }

    /**
     * Update a comment
     */
    public function updateComment(PostComments $comment, array $validatedData): array
    {
        $comment->update([
            'content' => $validatedData['content'],
        ]);

        return [
            'value' => true,
            'data' => $comment->fresh(),
            'message' => 'Comment updated successfully',
        ];
    }

    /**
     * Delete a comment
     */
    public function deleteComment(int $commentId): array
    {
        $comment = PostComments::find($commentId);

        if (! $comment) {
            return [
                'value' => false,
                'message' => 'No Post comment was found',
            ];
        }

        $comment->delete();

        // Clean up related notifications
        $this->cleanupCommentNotifications($commentId);

        return [
            'value' => true,
            'message' => 'Post comment Deleted Successfully',
        ];
    }

    /**
     * Check if comment exists
     */
    public function commentExists(int $commentId): bool
    {
        return PostComments::where('id', $commentId)->exists();
    }

    /**
     * Send notification to post owner when someone comments
     */
    private function sendCommentNotification(Posts $post, PostComments $comment, $commentingUser): void
    {
        try {
            $postOwner = $post->doctor;

            // Don't send notification if the commenter is the post owner
            if (! $postOwner || $postOwner->id === $commentingUser->id) {
                return;
            }

            // Create app notification
            AppNotification::createLocalized([
                'doctor_id' => $postOwner->id,
                'type' => 'PostComment',
                'type_id' => $post->id,
                'localization_key' => 'api.notification_post_commented',
                'localization_params' => [
                    'name' => $this->formatUserName($commentingUser),
                ],
                'type_doctor_id' => $commentingUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Post comment notification created', [
                'post_id' => $post->id,
                'comment_id' => $comment->id,
                'post_owner_id' => $postOwner->id,
                'commenting_user_id' => $commentingUser->id,
            ]);

            // Send push notification
            $tokens = FcmToken::where('doctor_id', $postOwner->id)
                ->pluck('token')
                ->toArray();

            if (! empty($tokens)) {
                $formattedName = $this->formatUserName($commentingUser);
                $this->notificationService->sendPushNotification(
                    __('api.new_comment_added'),
                    __('api.doctor_commented_on_post', ['name' => ucfirst($formattedName)]),
                    $tokens
                );
            }

        } catch (\Exception $e) {
            Log::error('Error sending post comment notification: '.$e->getMessage());
        }
    }
}
