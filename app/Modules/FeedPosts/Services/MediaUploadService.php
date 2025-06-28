<?php

namespace App\Modules\FeedPosts\Services;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MediaUploadService
{
    protected $mainController;

    public function __construct(MainController $mainController)
    {
        $this->mainController = $mainController;
    }

    /**
     * Handle media upload for posts
     */
    public function handleMediaUpload(Request $request, string $mediaType): array
    {
        if ($request->hasFile('media_path')) {
            $mediaPaths = [];
            foreach ($request->file('media_path') as $media) {
                $path = ($mediaType === 'image') ? 'media_images' : 'media_videos';
                $uploadResponse = $this->mainController->uploadImageAndVideo($media, $path);

                if ($uploadResponse->getData()->value) {
                    if (!in_array($uploadResponse->getData()->image, $mediaPaths)) {
                        $mediaPaths[] = $uploadResponse->getData()->image;
                        Log::info("Media Paths: ", $mediaPaths);
                    }                   
                } else {
                    throw new \Exception('Failed to upload media: ' . $uploadResponse->getData()->message);
                }
            }
            return $mediaPaths;
        }
        
        return [];
    }

    /**
     * Upload multiple images for post updates
     */
    public function uploadMultipleImages(array $files): array
    {
        $uploadedImages = [];

        foreach ($files as $media) {
            // Generate a unique filename
            $fileName = time() . '_' . Str::random(10) . '.' . $media->getClientOriginalExtension();

            // Store the media in the specified path (image or video directory)
            $storedPath = $media->storeAs('media_images', $fileName, 'public');

            // Construct the full URL for the uploaded media
            $mediaUrl = config('app.url') . '/storage/' . $storedPath;
            
            // Get the public URL of the uploaded file
            $uploadedImages[] = $mediaUrl;
        }

        return $uploadedImages;
    }

    /**
     * Validate media type against uploaded files
     */
    public function validateMediaType(Request $request): string
    {
        $mediaType = $request->input('media_type');
        
        if ($request->hasFile('media_path')) {
            $firstFile = $request->file('media_path')[0];
            $mimeType = $firstFile->getMimeType();
            
            if (str_starts_with($mimeType, 'image/')) {
                return 'image';
            } elseif (str_starts_with($mimeType, 'video/')) {
                return 'video';
            }
        }
        
        return $mediaType ?: 'text';
    }
}