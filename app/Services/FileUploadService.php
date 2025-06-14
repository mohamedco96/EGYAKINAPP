<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileUploadService
{
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
            $filename = $file->getClientOriginalName();
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
                'message' => 'File upload failed: ' . $e->getMessage(),
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

                    try {
                        $fileContent = base64_decode($fileData);
                        $filePath = 'medical_reports/' . $fileName;
                        
                        Storage::disk('public')->put($filePath, $fileContent);
                        $fileUrl = Storage::disk('public')->url($filePath);
                        
                        $fileUrls[$key][] = $fileUrl;
                        
                        Log::info("File uploaded successfully: {$fileName}");
                    } catch (\Exception $e) {
                        Log::error("Failed to upload file {$fileName}: " . $e->getMessage());
                        throw new \Exception("Failed to upload file {$fileName}: " . $e->getMessage());
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

            try {
                $fileContent = base64_decode($fileData);
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
