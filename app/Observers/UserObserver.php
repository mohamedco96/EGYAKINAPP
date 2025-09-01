<?php

namespace App\Observers;

use App\Models\User;
use App\Services\FileCleanupService;

class UserObserver
{
    protected FileCleanupService $fileCleanupService;

    public function __construct(FileCleanupService $fileCleanupService)
    {
        $this->fileCleanupService = $fileCleanupService;
    }

    /**
     * Handle the User "deleting" event.
     */
    public function deleting(User $user): void
    {
        // Define which columns contain files and their configuration
        $fileColumns = [
            'image' => [
                'type' => 'string',
                'disk' => 'public',
            ],
        ];

        // Delete associated files
        $this->fileCleanupService->deleteModelFiles($user, $fileColumns);
    }

    /**
     * Handle the User "updating" event.
     * Clean up old profile image when it's being changed.
     */
    public function updating(User $user): void
    {
        // Check if image is being changed
        if ($user->isDirty('image')) {
            $oldImage = $user->getOriginal('image');

            if ($oldImage && $oldImage !== $user->image) {
                $cleanPath = $this->fileCleanupService->extractFilePathFromUrl($oldImage);
                if ($cleanPath) {
                    $this->fileCleanupService->deleteFile($cleanPath, 'public');
                }
            }
        }
    }
}

