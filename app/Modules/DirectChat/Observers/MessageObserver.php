<?php

namespace App\Modules\DirectChat\Observers;

use App\Modules\DirectChat\Models\Message;
use App\Modules\DirectChat\Services\ChatFileService;
use Illuminate\Support\Facades\Log;
use Throwable;

class MessageObserver
{
    public function __construct(private readonly ChatFileService $fileService) {}

    /**
     * When a message is soft-deleted (e.g. "delete for everyone"),
     * remove the associated file from the private disk to prevent storage leaks.
     */
    public function deleted(Message $message): void
    {
        $this->deleteFileIfPresent($message);
    }

    /**
     * Also clean up on force delete, in case a message is permanently removed.
     */
    public function forceDeleted(Message $message): void
    {
        $this->deleteFileIfPresent($message);
    }

    private function deleteFileIfPresent(Message $message): void
    {
        $metadata = $message->file_metadata;

        if (empty($metadata) || empty($metadata['disk_path'])) {
            return;
        }

        try {
            $this->fileService->deleteFile($metadata['disk_path']);
        } catch (Throwable $e) {
            Log::warning('DirectChat: Failed to delete file on message deletion', [
                'message_id' => $message->id,
                'disk_path' => $metadata['disk_path'],
                'error' => $e->getMessage(),
            ]);
        }
    }
}
