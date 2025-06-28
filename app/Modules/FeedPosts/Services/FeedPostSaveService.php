<?php

namespace App\Modules\FeedPosts\Services;

use App\Models\FeedPost;
use App\Models\FeedSaveLike;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FeedPostSaveService
{
    /**
     * Save or unsave a post
     */
    public function saveOrUnsavePost(int $postId, string $status): array
    {
        try {
            $doctor_id = Auth::id();

            // Check if the post exists
            $post = FeedPost::findOrFail($postId);

            // Find if the post is already saved
            $save = FeedSaveLike::where('feed_post_id', $postId)
                ->where('doctor_id', $doctor_id)
                ->first();

            // Handle Save
            if ($status === 'save') {
                if ($save) {
                    Log::warning("Post already saved PostID: $postId UserID: $doctor_id");
                    return [
                        'value' => false,
                        'message' => 'Post already saved'
                    ];
                }

                // Create a new save entry
                $newSave = FeedSaveLike::create([
                    'feed_post_id' => $postId,
                    'doctor_id' => $doctor_id,
                ]);

                Log::info("Post ID $postId saved by doctor " . $doctor_id);
                return [
                    'value' => true,
                    'data' => $newSave,
                    'message' => 'Post saved successfully'
                ];

            // Handle Unsave
            } elseif ($status === 'unsave') {
                if ($save) {
                    $save->delete();
                    Log::info("Post ID $postId unsaved by doctor " . $doctor_id);
                    return [
                        'value' => true,
                        'message' => 'Post unsaved successfully'
                    ];
                }

                Log::warning("Save not found for post ID $postId");
                return [
                    'value' => false,
                    'message' => 'Save not found'
                ];
            } else {
                Log::warning("Invalid status for post save/unsave: $status");
                return [
                    'value' => false,
                    'message' => 'Invalid status. Use "save" or "unsave".'
                ];
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $postId not found for save/unsave");
            return [
                'value' => false,
                'message' => 'Post not found'
            ];
        } catch (\Exception $e) {
            Log::error("Error processing save/unsave for post ID $postId: " . $e->getMessage());
            return [
                'value' => false,
                'message' => 'An error occurred while processing the request'
            ];
        }
    }
}
