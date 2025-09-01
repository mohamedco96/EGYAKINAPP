<?php

namespace App\Observers;

use App\Models\FeedPost;
use App\Services\FileCleanupService;

class FeedPostObserver
{
    protected FileCleanupService $fileCleanupService;

    public function __construct(FileCleanupService $fileCleanupService)
    {
        $this->fileCleanupService = $fileCleanupService;
    }

    /**
     * Handle the FeedPost "deleting" event.
     * This fires before the model is actually deleted from the database.
     */
    public function deleting(FeedPost $feedPost): void
    {
        // Define which columns contain files and their configuration
        $fileColumns = [
            'media_path' => [
                'type' => 'json_array',
                'disk' => 'public',
            ],
        ];

        // Delete associated files
        $this->fileCleanupService->deleteModelFiles($feedPost, $fileColumns);
    }

    /**
     * Handle the FeedPost "updating" event.
     * Clean up files that are being removed/changed.
     */
    public function updating(FeedPost $feedPost): void
    {
        // Check if media_path is being changed
        if ($feedPost->isDirty('media_path')) {
            $oldMediaPath = $feedPost->getOriginal('media_path');
            $newMediaPath = $feedPost->media_path;

            // Get old files
            $oldFiles = [];
            if ($oldMediaPath) {
                $decoded = is_string($oldMediaPath) ? json_decode($oldMediaPath, true) : $oldMediaPath;
                if (is_array($decoded)) {
                    $oldFiles = $decoded;
                }
            }

            // Get new files
            $newFiles = [];
            if ($newMediaPath) {
                $decoded = is_string($newMediaPath) ? json_decode($newMediaPath, true) : $newMediaPath;
                if (is_array($decoded)) {
                    $newFiles = $decoded;
                }
            }

            // Find files that are being removed
            $removedFiles = array_diff($oldFiles, $newFiles);

            // Delete removed files
            foreach ($removedFiles as $filePath) {
                $cleanPath = $this->fileCleanupService->extractFilePathFromUrl($filePath);
                if ($cleanPath) {
                    $this->fileCleanupService->deleteFile($cleanPath, 'public');
                }
            }
        }
    }
}

