<?php

namespace App\Modules\Posts\Services;

use App\Modules\Posts\Models\Posts;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class PostService
{
    /**
     * Get all visible posts with doctor information
     */
    public function getAllPosts(): array
    {
        $posts = Posts::select('id', 'title', 'image', 'content', 'hidden', 'post_type', 'webinar_date', 'url', 'doctor_id', 'updated_at')
            ->where('hidden', 0)
            ->with('doctor:id,name,lname')
            ->get();

        if ($posts->isNotEmpty()) {
            return [
                'value' => true,
                'data' => $posts,
            ];
        }

        return [
            'value' => false,
            'message' => 'No post was found',
        ];
    }

    /**
     * Get a specific post by ID
     */
    public function getPostById(int $id): array
    {
        $post = Posts::where('id', $id)
            ->select('id', 'title', 'image', 'content', 'hidden', 'post_type', 'webinar_date', 'url', 'doctor_id', 'updated_at')
            ->with('doctor:id,name,lname')
            ->get();

        return [
            'value' => true,
            'data' => $post,
        ];
    }

    /**
     * Create a new post
     */
    public function createPost(array $validatedData, ?UploadedFile $imageFile = null): array
    {
        $imagePath = null;
        
        if ($imageFile) {
            $imagePath = $this->handleImageUpload($imageFile);
        }

        $post = new Posts([
            'title' => $validatedData['title'],
            'image' => $imagePath,
            'content' => $validatedData['content'],
        ]);

        $user = Auth::user();
        $user->posts()->save($post);

        if ($post->exists) {
            return [
                'value' => true,
                'data' => $post,
            ];
        }

        return [
            'value' => false,
            'message' => 'Failed to create post',
        ];
    }

    /**
     * Handle image upload for posts
     */
    private function handleImageUpload(UploadedFile $image): string
    {
        $imagePath = $image->store('images', 'public');
        return Storage::disk('public')->url($imagePath);
    }

    /**
     * Update a post
     */
    public function updatePost(Posts $post, array $validatedData, ?UploadedFile $imageFile = null): array
    {
        $imagePath = $post->image;
        
        if ($imageFile) {
            // Delete old image if exists
            if ($post->image) {
                $oldImagePath = str_replace(Storage::disk('public')->url(''), '', $post->image);
                Storage::disk('public')->delete($oldImagePath);
            }
            
            $imagePath = $this->handleImageUpload($imageFile);
        }

        $post->update([
            'title' => $validatedData['title'] ?? $post->title,
            'image' => $imagePath,
            'content' => $validatedData['content'] ?? $post->content,
        ]);

        return [
            'value' => true,
            'data' => $post->fresh(),
        ];
    }

    /**
     * Delete a post
     */
    public function deletePost(Posts $post): array
    {
        // Delete associated image if exists
        if ($post->image) {
            $imagePath = str_replace(Storage::disk('public')->url(''), '', $post->image);
            Storage::disk('public')->delete($imagePath);
        }

        $post->delete();

        return [
            'value' => true,
            'message' => 'Post deleted successfully',
        ];
    }
}
