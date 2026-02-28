<?php

namespace App\Observers;

use App\Modules\Posts\Models\Posts;
use App\Services\FileCleanupService;

class PostsObserver
{
    protected FileCleanupService $fileCleanupService;

    public function __construct(FileCleanupService $fileCleanupService)
    {
        $this->fileCleanupService = $fileCleanupService;
    }

    /**
     * Handle the Posts "deleting" event.
     * This fires before the model is actually deleted from the database.
     */
    public function deleting(Posts $post): void
    {
        // Define which columns contain files and their configuration
        $fileColumns = [
            'image' => [
                'type' => 'string',
                'disk' => 'public',
            ],
        ];

        // Delete associated files
        $this->fileCleanupService->deleteModelFiles($post, $fileColumns);
    }

    /**
     * Handle the Posts "updating" event.
     * Clean up files that are being removed/changed.
     */
    public function updating(Posts $post): void
    {
        // Check if image is being changed
        if ($post->isDirty('image')) {
            $oldFile = $post->getOriginal('image');
            if ($oldFile) {
                $cleanPath = $this->fileCleanupService->extractFilePathFromUrl($oldFile);
                if ($cleanPath) {
                    $this->fileCleanupService->deleteFile($cleanPath, 'public');
                }
            }
        }
    }
}
