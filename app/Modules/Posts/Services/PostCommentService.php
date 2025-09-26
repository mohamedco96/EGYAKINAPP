<?php

namespace App\Modules\Posts\Services;

use App\Modules\Posts\Models\PostComments;
use App\Modules\Posts\Models\Posts;
use App\Traits\NotificationCleanup;
use Illuminate\Support\Facades\Auth;

class PostCommentService
{
    use NotificationCleanup;

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
}
