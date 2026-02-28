<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileUploadService
{
    /**
     * Sanitize a user-supplied filename to prevent path traversal.
     */
    private function sanitizeFilename(string $fileName): string
    {
        $fileName = basename($fileName);
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);

        return $fileName ?: 'file';
    }

    /**
     * Decode base64 data strictly and enforce an extension allowlist + MIME check.
     *
     * @throws \Exception
     */
    private function decodeBase64File(string $fileData, string $fileName): string
    {
        // Reject dotfiles (e.g. .htaccess, .env)
        if (str_starts_with($fileName, '.')) {
            throw new \Exception("Dotfiles are not permitted.");
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Allowlist of permitted extensions mapped to their acceptable MIME types.
        // docx/xlsx are ZIP-based; finfo often returns application/zip for them.
        $allowed = [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'gif'  => ['image/gif'],
            'webp' => ['image/webp'],
            'pdf'  => ['application/pdf'],
            'doc'  => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'xls'  => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'txt'  => ['text/plain'],
            'csv'  => ['text/csv', 'text/plain'],
        ];

        if ($ext === '' || !array_key_exists($ext, $allowed)) {
            throw new \Exception("File extension '{$ext}' is not permitted.");
        }

        $decoded = base64_decode($fileData, true);

        if ($decoded === false) {
            throw new \Exception('Invalid base64 file data.');
        }

        // Verify actual MIME type from decoded bytes matches the declared extension.
        $finfo        = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->buffer($decoded);

        if (!in_array($detectedMime, $allowed[$ext], true)) {
            throw new \Exception("File MIME type '{$detectedMime}' does not match declared extension '{$ext}'.");
        }

        return $decoded;
    }

    /**
     * Upload a single file and return its path
     */
    public function uploadFile($file, string $directory = 'reports'): array
    {
        if (!$file) {
            return [
                'success' => false,
                'message' => 'Please select a file to upload.',
            ];
        }

        try {
            $filename = $this->sanitizeFilename($file->getClientOriginalName());
            $path = $file->storeAs($directory, random_int(500, 10000000000) . '_' . $filename, 'public');
            $fileUrl = config('app.url') . '/storage/' . $path;

            return [
                'success' => true,
                'message' => 'File uploaded successfully.',
                'file' => $filename,
                'path' => $path,
                'full_path' => $fileUrl,
            ];
        } catch (\Exception $e) {
            Log::error('File upload failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'File upload failed.',
            ];
        }
    }

    /**
     * Upload multiple files from base64 data
     */
    public function uploadMultipleFiles(array $requestData): array
    {
        $fileUrls = [];

        foreach ($requestData as $key => $files) {
            if (is_array($files)) {
                foreach ($files as $file) {
                    $fileData = $file['file_data'] ?? null;
                    $fileName = $file['file_name'] ?? null;

                    if (!$fileData || !$fileName) {
                        throw new \Exception('File name or data is missing');
                    }

                    $fileName = $this->sanitizeFilename($fileName);

                    try {
                        $fileContent = $this->decodeBase64File($fileData, $fileName);
                        $filePath = 'medical_reports/' . $fileName;

                        Storage::disk('public')->put($filePath, $fileContent);
                        $fileUrl = Storage::disk('public')->url($filePath);

                        $fileUrls[$key][] = $fileUrl;

                        Log::info("File uploaded successfully: {$fileName}");
                    } catch (\Exception $e) {
                        Log::error("Failed to upload file {$fileName}: " . $e->getMessage());
                        throw new \Exception("Failed to upload file {$fileName}.");
                    }
                }
            }
        }

        return $fileUrls;
    }

    /**
     * Handle file uploads for question answers
     */
    public function handleQuestionFileUploads(array $files): array
    {
        $filePaths = [];

        foreach ($files as $file) {
            $fileData = $file['file_data'] ?? null;
            $fileName = $file['file_name'] ?? null;

            if (!$fileData || !$fileName) {
                throw new \Exception('File name or data is missing');
            }

            $fileName = $this->sanitizeFilename($fileName);

            try {
                $fileContent = $this->decodeBase64File($fileData, $fileName);
                $filePath = 'medical_reports/' . $fileName;

                Storage::disk('public')->put($filePath, $fileContent);
                $filePaths[] = $filePath;

                Log::info("File uploaded successfully: {$fileName}");
            } catch (\Exception $e) {
                Log::error("Failed to upload file {$fileName}: " . $e->getMessage());
                // Continue with other files instead of failing completely
            }
        }

        return $filePaths;
    }
}
