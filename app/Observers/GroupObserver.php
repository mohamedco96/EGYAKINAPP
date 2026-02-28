<?php

namespace App\Observers;

use App\Models\Group;
use App\Services\FileCleanupService;

class GroupObserver
{
    protected FileCleanupService $fileCleanupService;

    /**
     * File paths staged for deletion, keyed by "{class}:{id}" to support batches.
     * Populated in pre-commit hooks; consumed in post-commit hooks.
     *
     * @var array<string, list<array{path: string, disk: string}>>
     */
    protected array $stagedFiles = [];

    public function __construct(FileCleanupService $fileCleanupService)
    {
        $this->fileCleanupService = $fileCleanupService;
    }

    /**
     * Handle the Group "deleting" event.
     * Stage file paths for deletion; actual removal happens in deleted().
     */
    public function deleting(Group $group): void
    {
        $fileColumns = [
            'header_picture' => ['type' => 'string', 'disk' => 'public'],
            'group_image'    => ['type' => 'string', 'disk' => 'public'],
        ];

        $staged = [];
        foreach ($fileColumns as $column => $config) {
            $value = $group->{$column};
            if ($value) {
                $cleanPath = $this->fileCleanupService->extractFilePathFromUrl($value);
                if ($cleanPath) {
                    $staged[] = ['path' => $cleanPath, 'disk' => $config['disk']];
                }
            }
        }

        $this->stagedFiles[$this->key($group)] = $staged;
    }

    /**
     * Handle the Group "deleted" event.
     * Runs after the DB commit — safe to delete physical files now.
     */
    public function deleted(Group $group): void
    {
        $key = $this->key($group);
        foreach ($this->stagedFiles[$key] ?? [] as ['path' => $path, 'disk' => $disk]) {
            $this->fileCleanupService->deleteFile($path, $disk);
        }
        unset($this->stagedFiles[$key]);
    }

    /**
     * Handle the Group "updating" event.
     * Stage old file paths for deletion; actual removal happens in updated().
     */
    public function updating(Group $group): void
    {
        $staged = [];

        if ($group->isDirty('header_picture')) {
            $oldFile = $group->getOriginal('header_picture');
            if ($oldFile) {
                $cleanPath = $this->fileCleanupService->extractFilePathFromUrl($oldFile);
                if ($cleanPath) {
                    $staged[] = ['path' => $cleanPath, 'disk' => 'public'];
                }
            }
        }

        if ($group->isDirty('group_image')) {
            $oldFile = $group->getOriginal('group_image');
            if ($oldFile) {
                $cleanPath = $this->fileCleanupService->extractFilePathFromUrl($oldFile);
                if ($cleanPath) {
                    $staged[] = ['path' => $cleanPath, 'disk' => 'public'];
                }
            }
        }

        $this->stagedFiles[$this->key($group)] = $staged;
    }

    /**
     * Handle the Group "updated" event.
     * Runs after the DB commit — safe to delete superseded files now.
     */
    public function updated(Group $group): void
    {
        $key = $this->key($group);
        foreach ($this->stagedFiles[$key] ?? [] as ['path' => $path, 'disk' => $disk]) {
            $this->fileCleanupService->deleteFile($path, $disk);
        }
        unset($this->stagedFiles[$key]);
    }

    /**
     * Unique staging key per model instance.
     */
    private function key(Group $group): string
    {
        return Group::class . ':' . $group->getKey();
    }
}
