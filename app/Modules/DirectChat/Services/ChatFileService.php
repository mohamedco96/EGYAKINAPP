<?php

namespace App\Modules\DirectChat\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ChatFileService
{
    private const DISK = 'chat_private';

    private const ALLOWED_TYPES = [
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'voice' => ['audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/wav', 'audio/aac', 'audio/x-m4a'],
        'file' => ['application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain', 'text/csv'],
    ];

    private const SUBDIRS = [
        'image' => 'images',
        'voice' => 'voice',
        'file' => 'files',
    ];

    /**
     * Upload a chat file to the private disk.
     *
     * Returns metadata array: {original_name, disk_path, mime_type, size_bytes}
     *
     * @throws \InvalidArgumentException when MIME type is not allowed
     */
    public function uploadFile(UploadedFile $file, string $messageType): array
    {
        $mime = $file->getMimeType();
        $allowed = self::ALLOWED_TYPES[$messageType] ?? [];

        if (! in_array($mime, $allowed, true)) {
            throw new \InvalidArgumentException(
                "File type '{$mime}' is not allowed for message type '{$messageType}'."
            );
        }

        $subdir = self::SUBDIRS[$messageType] ?? 'files';
        $ext = $file->getClientOriginalExtension();
        $filename = Str::uuid().'_'.time().($ext ? ".{$ext}" : '');
        $diskPath = "{$subdir}/{$filename}";

        if (Storage::disk(self::DISK)->putFileAs($subdir, $file, $filename) === false) {
            throw new \RuntimeException("Failed to store uploaded file '{$filename}'.");
        }

        return [
            'original_name' => $file->getClientOriginalName(),
            'disk_path' => $diskPath,
            'mime_type' => $mime,
            'size_bytes' => $file->getSize(),
        ];
    }

    /**
     * Generate a signed temporary URL (valid 30 minutes) for a stored file.
     * Uses a signed route since the local disk does not support temporaryUrl().
     * Participant access is verified server-side via the authenticated session.
     */
    public function getFileUrl(int $messageId): string
    {
        return URL::signedRoute(
            'chat.file.download',
            ['messageId' => $messageId],
            now()->addMinutes(30)
        );
    }

    /**
     * Delete a file from the private disk.
     */
    public function deleteFile(string $diskPath): bool
    {
        if (! Storage::disk(self::DISK)->exists($diskPath)) {
            return false;
        }

        return Storage::disk(self::DISK)->delete($diskPath);
    }
}
