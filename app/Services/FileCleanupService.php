<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileCleanupService
{
    /**
     * Delete files associated with a model record
     */
    public function deleteModelFiles($model, array $fileColumns = []): bool
    {
        if (! Config::get('filesystems.cleanup.auto_cleanup_on_delete', true)) {
            return false;
        }

        $deletedFiles = [];
        $errors = [];

        try {
            foreach ($fileColumns as $column => $config) {
                $files = $this->extractFilesFromModel($model, $column, $config);

                foreach ($files as $filePath) {
                    if ($this->deleteFile($filePath, $config['disk'] ?? 'public')) {
                        $deletedFiles[] = $filePath;
                    } else {
                        $errors[] = $filePath;
                    }
                }
            }

            // Log the cleanup operation
            $this->logModelCleanup($model, $deletedFiles, $errors);

            return empty($errors);

        } catch (\Exception $e) {
            Log::error('Error during model file cleanup', [
                'model' => get_class($model),
                'model_id' => $model->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Extract file paths from a model attribute
     */
    protected function extractFilesFromModel($model, string $column, array $config): array
    {
        $value = $model->{$column};

        if (empty($value)) {
            return [];
        }

        $files = [];

        switch ($config['type']) {
            case 'json_array':
                $decoded = is_string($value) ? json_decode($value, true) : $value;
                if (is_array($decoded)) {
                    $files = array_filter($decoded);
                }
                break;

            case 'string':
                $files = [$value];
                break;

            case 'comma_separated':
                $files = array_filter(explode(',', $value));
                break;

            default:
                $files = [$value];
                break;
        }

        // Convert URLs to file paths
        return array_map([$this, 'extractFilePathFromUrl'], array_filter($files));
    }

    /**
     * Extract file path from URL
     */
    public function extractFilePathFromUrl(string $url): ?string
    {
        // Handle full URLs
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $path = parse_url($url, PHP_URL_PATH);
            if ($path) {
                // Remove /storage/ prefix if present
                return preg_replace('/^\/storage\//', '', $path);
            }
        }

        // Handle relative paths
        if (strpos($url, '/storage/') === 0) {
            return substr($url, 9); // Remove /storage/
        }

        // Return as-is if it's already a storage path
        return $url;
    }

    /**
     * Delete a single file
     */
    public function deleteFile(string $filePath, string $disk = 'public'): bool
    {
        try {
            $storage = Storage::disk($disk);

            if (! $storage->exists($filePath)) {
                return true; // File doesn't exist, consider it "deleted"
            }

            // Check if file should be excluded
            if ($this->shouldExcludeFile(basename($filePath))) {
                Log::info('File excluded from deletion', ['file' => $filePath]);

                return false;
            }

            // Optional: Create backup before deletion
            if (Config::get('filesystems.cleanup.safety.backup_before_delete', false)) {
                $this->backupFile($filePath, $disk);
            }

            $deleted = $storage->delete($filePath);

            if ($deleted) {
                Log::info('File deleted successfully', [
                    'file' => $filePath,
                    'disk' => $disk,
                    'timestamp' => Carbon::now()->toISOString(),
                ]);
            }

            return $deleted;

        } catch (\Exception $e) {
            Log::error('Failed to delete file', [
                'file' => $filePath,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if file should be excluded from deletion
     */
    protected function shouldExcludeFile(string $fileName): bool
    {
        $excludePatterns = Config::get('filesystems.cleanup.exclude_patterns', []);

        foreach ($excludePatterns as $pattern) {
            if (fnmatch($pattern, $fileName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a backup of the file before deletion
     */
    protected function backupFile(string $filePath, string $sourceDisk): bool
    {
        try {
            $backupDisk = Config::get('filesystems.cleanup.safety.backup_disk', 'local');
            $backupPath = 'backups/deleted_files/'.date('Y/m/d').'/'.basename($filePath);

            $sourceStorage = Storage::disk($sourceDisk);
            $backupStorage = Storage::disk($backupDisk);

            $content = $sourceStorage->get($filePath);
            $backed = $backupStorage->put($backupPath, $content);

            if ($backed) {
                Log::info('File backed up before deletion', [
                    'original' => $filePath,
                    'backup' => $backupPath,
                    'source_disk' => $sourceDisk,
                    'backup_disk' => $backupDisk,
                ]);
            }

            return $backed;

        } catch (\Exception $e) {
            Log::warning('Failed to backup file before deletion', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Log model cleanup operation
     */
    protected function logModelCleanup($model, array $deletedFiles, array $errors): void
    {
        $logData = [
            'model' => get_class($model),
            'model_id' => $model->id ?? 'unknown',
            'deleted_files' => $deletedFiles,
            'errors' => $errors,
            'deleted_count' => count($deletedFiles),
            'error_count' => count($errors),
            'timestamp' => Carbon::now()->toISOString(),
        ];

        if (empty($errors)) {
            Log::info('Model files cleaned up successfully', $logData);
        } else {
            Log::warning('Model file cleanup completed with errors', $logData);
        }

        // Also log to dedicated cleanup channel
        Log::channel('file_cleanup')->info('Model file cleanup', $logData);
    }

    /**
     * Get file age in days
     */
    public function getFileAge(string $filePath, string $disk = 'public'): int
    {
        try {
            $storage = Storage::disk($disk);
            $lastModified = $storage->lastModified($filePath);

            if ($lastModified) {
                $modifiedDate = Carbon::createFromTimestamp($lastModified);

                return $modifiedDate->diffInDays(Carbon::now());
            }

            return 0;

        } catch (\Exception $e) {
            Log::warning('Could not determine file age', [
                'file' => $filePath,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Check if file is safe to delete based on age
     */
    public function isFileSafeToDelete(string $filePath, string $disk = 'public'): bool
    {
        $retentionDays = Config::get('filesystems.cleanup.retention_days', 7);
        $fileAge = $this->getFileAge($filePath, $disk);

        return $fileAge >= $retentionDays;
    }
}

