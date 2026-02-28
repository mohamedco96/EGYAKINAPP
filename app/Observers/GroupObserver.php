<?php

namespace App\Observers;

use App\Models\Group;
use App\Services\FileCleanupService;

class GroupObserver
{
    protected FileCleanupService $fileCleanupService;

    public function __construct(FileCleanupService $fileCleanupService)
    {
        $this->fileCleanupService = $fileCleanupService;
    }

    /**
     * Handle the Group "deleting" event.
     * This fires before the model is actually deleted from the database.
     */
    public function deleting(Group $group): void
    {
        // Define which columns contain files and their configuration
        $fileColumns = [
            'header_picture' => [
                'type' => 'string',
                'disk' => 'public',
            ],
            'group_image' => [
                'type' => 'string',
                'disk' => 'public',
            ],
        ];

        // Delete associated files
        $this->fileCleanupService->deleteModelFiles($group, $fileColumns);
    }

    /**
     * Handle the Group "updating" event.
     * Clean up files that are being removed/changed.
     */
    public function updating(Group $group): void
    {
        // Check if header_picture is being changed
        if ($group->isDirty('header_picture')) {
            $oldFile = $group->getOriginal('header_picture');
            if ($oldFile) {
                $cleanPath = $this->fileCleanupService->extractFilePathFromUrl($oldFile);
                if ($cleanPath) {
                    $this->fileCleanupService->deleteFile($cleanPath, 'public');
                }
            }
        }

        // Check if group_image is being changed
        if ($group->isDirty('group_image')) {
            $oldFile = $group->getOriginal('group_image');
            if ($oldFile) {
                $cleanPath = $this->fileCleanupService->extractFilePathFromUrl($oldFile);
                if ($cleanPath) {
                    $this->fileCleanupService->deleteFile($cleanPath, 'public');
                }
            }
        }
    }
}
