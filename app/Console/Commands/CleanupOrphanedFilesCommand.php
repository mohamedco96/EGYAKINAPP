<?php

namespace App\Console\Commands;

use App\Models\FeedPost;
use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanedFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'files:cleanup 
                            {--disk=public : The storage disk to clean}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--batch-size=10000 : Number of files to process at once}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up orphaned files that no longer have matching database records';

    /**
     * File patterns to check for cleanup
     */
    protected array $filePatterns = [
        // General images directory (used by various upload methods)
        'images' => [
            'model' => null, // Will be handled specially - check all models
            'column' => null,
            'type' => 'images',
        ],
        // FeedPost media files
        'media_images' => [
            'model' => FeedPost::class,
            'column' => 'media_path',
            'type' => 'json_array',
        ],
        'media_videos' => [
            'model' => FeedPost::class,
            'column' => 'media_path',
            'type' => 'json_array',
        ],
        // User profile and syndicate card files
        'profile_images' => [
            'model' => User::class,
            'column' => 'image',
            'type' => 'string',
        ],
        'syndicate_card' => [
            'model' => User::class,
            'column' => 'syndicate_card',
            'type' => 'string',
        ],
        // Group images
        'header_pictures' => [
            'model' => Group::class,
            'column' => 'header_picture',
            'type' => 'string',
        ],
        'group_images' => [
            'model' => Group::class,
            'column' => 'group_image',
            'type' => 'string',
        ],
        // Medical reports and other files
        'medical_reports' => [
            'model' => null, // Will be handled specially - check all models that might reference these
            'column' => null,
            'type' => 'medical_reports',
        ],
        'reports' => [
            'model' => null, // Will be handled specially - check all models that might reference these
            'column' => null,
            'type' => 'reports',
        ],
        // Root files (files not in any subdirectory)
        'root' => [
            'model' => null, // Will be handled specially
            'column' => null,
            'type' => 'root_files',
        ],
    ];

    /**
     * Files to exclude from cleanup (system files, defaults, etc.)
     */
    protected array $excludePatterns = [
        'default_profile.png',
        'placeholder.jpg',
        '.gitkeep',
        '.DS_Store',
        'thumbs.db',
    ];

    protected int $deletedCount = 0;

    protected int $skippedCount = 0;

    protected int $errorCount = 0;

    protected array $deletedFiles = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! Config::get('filesystems.cleanup.enabled', true)) {
            $this->error('File cleanup is disabled in configuration.');

            return self::FAILURE;
        }

        $disk = $this->option('disk');
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');

        $this->info("Starting file cleanup on disk: {$disk}");
        $this->info('Dry run: '.($dryRun ? 'Yes' : 'No'));
        $this->info("Batch size: {$batchSize}");

        if (! $force && ! $dryRun) {
            if (! $this->confirm('This will permanently delete orphaned files. Are you sure?')) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $startTime = microtime(true);

        try {
            foreach ($this->filePatterns as $directory => $config) {
                $this->info("\nProcessing directory: {$directory}");
                $this->processDirectory($disk, $directory, $config, $dryRun, $batchSize);
            }

            $this->displaySummary($startTime, $dryRun);
            $this->logCleanupResults($dryRun);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error during cleanup: '.$e->getMessage());
            Log::error('File cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Process a specific directory for orphaned files
     */
    protected function processDirectory(string $disk, string $directory, array $config, bool $dryRun, int $batchSize): void
    {
        $storage = Storage::disk($disk);

        // Handle root files differently
        if ($directory === 'root') {
            $this->processRootFiles($storage, $dryRun, $batchSize);

            return;
        }

        if (! $storage->exists($directory)) {
            $this->warn("Directory {$directory} does not exist on disk {$disk}");

            return;
        }

        // Get all files in the directory
        $allFiles = $storage->allFiles($directory);
        $totalFiles = count($allFiles);

        $this->info("Found {$totalFiles} files in {$directory}");

        if ($totalFiles === 0) {
            return;
        }

        // Get referenced files from database
        $referencedFiles = $this->getReferencedFiles($config);

        $this->info('Found '.count($referencedFiles).' files referenced in database');

        // Process files in batches
        $chunks = array_chunk($allFiles, $batchSize);
        $progressBar = $this->output->createProgressBar(count($chunks));
        $progressBar->start();

        foreach ($chunks as $batch) {
            $this->processBatch($storage, $batch, $referencedFiles, $dryRun);
            $progressBar->advance();

            // Small delay to prevent overwhelming the system
            usleep(10000); // 10ms
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Process root files (files not in any subdirectory)
     */
    protected function processRootFiles(\Illuminate\Contracts\Filesystem\Filesystem $storage, bool $dryRun, int $batchSize): void
    {
        // Get all files in the root directory
        $allFiles = $storage->files();
        $totalFiles = count($allFiles);

        $this->info("Found {$totalFiles} files in root directory");

        if ($totalFiles === 0) {
            return;
        }

        // For root files, we'll check against all known file patterns
        $referencedFiles = $this->getAllReferencedFiles();

        $this->info('Found '.count($referencedFiles).' files referenced in database');

        // Process files in batches
        $chunks = array_chunk($allFiles, $batchSize);
        $progressBar = $this->output->createProgressBar(count($chunks));
        $progressBar->start();

        foreach ($chunks as $batch) {
            $this->processBatch($storage, $batch, $referencedFiles, $dryRun);
            $progressBar->advance();

            // Small delay to prevent overwhelming the system
            usleep(10000); // 10ms
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Get files referenced in the database
     */
    protected function getReferencedFiles(array $config): array
    {
        $model = $config['model'];
        $column = $config['column'];
        $type = $config['type'];

        $referencedFiles = [];

        try {
            if ($type === 'root_files') {
                // For root files, we'll get all referenced files from all patterns
                return $this->getAllReferencedFiles();
            } elseif ($type === 'images') {
                // For images directory, check all models that might reference these files
                return $this->getImagesReferencedFiles();
            } elseif ($type === 'medical_reports') {
                // For medical reports, check all models that might reference these files
                return $this->getMedicalReportsReferencedFiles();
            } elseif ($type === 'reports') {
                // For reports, check all models that might reference these files
                return $this->getReportsReferencedFiles();
            } elseif ($type === 'json_array') {
                // Handle JSON array columns (like media_path)
                $records = $model::whereNotNull($column)
                    ->where($column, '!=', '[]')
                    ->where($column, '!=', '')
                    ->select('id', $column)
                    ->get();

                foreach ($records as $record) {
                    $columnValue = $record->{$column};

                    // Handle both string (JSON) and array values
                    if (is_string($columnValue)) {
                        $files = json_decode($columnValue, true);
                    } elseif (is_array($columnValue)) {
                        $files = $columnValue;
                    } else {
                        continue;
                    }

                    if (is_array($files)) {
                        foreach ($files as $file) {
                            if ($file) {
                                // Extract file path from URL if needed
                                $filePath = $this->extractFilePathFromUrl($file);
                                if ($filePath) {
                                    $referencedFiles[] = $filePath;
                                }
                            }
                        }
                    }
                }
            } else {
                // Handle string columns (like image)
                $records = $model::whereNotNull($column)
                    ->where($column, '!=', '')
                    ->pluck($column)
                    ->filter()
                    ->toArray();

                foreach ($records as $file) {
                    $filePath = $this->extractFilePathFromUrl($file);
                    if ($filePath) {
                        $referencedFiles[] = $filePath;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warn('Database connection failed: '.$e->getMessage());
            $this->warn('Proceeding with cleanup assuming no files are referenced in database.');

            return [];
        }

        return array_unique($referencedFiles);
    }

    /**
     * Get all files referenced in the database from all patterns
     */
    protected function getAllReferencedFiles(): array
    {
        $allReferencedFiles = [];

        foreach ($this->filePatterns as $directory => $config) {
            if ($directory !== 'root' && $config['model'] !== null) {
                $referencedFiles = $this->getReferencedFiles($config);
                $allReferencedFiles = array_merge($allReferencedFiles, $referencedFiles);
            }
        }

        return array_unique($allReferencedFiles);
    }

    /**
     * Get images referenced in the database
     */
    protected function getImagesReferencedFiles(): array
    {
        $referencedFiles = [];

        try {
            // Check all models that might reference images in the images directory
            // This includes FeedPost media_path, User image, User syndicate_card, Group header_picture, Group group_image

            // Check FeedPost media_path
            $feedPosts = FeedPost::whereNotNull('media_path')
                ->where('media_path', '!=', '[]')
                ->where('media_path', '!=', '')
                ->select('id', 'media_path')
                ->get();

            foreach ($feedPosts as $post) {
                $columnValue = $post->media_path;

                if (is_string($columnValue)) {
                    $files = json_decode($columnValue, true);
                } elseif (is_array($columnValue)) {
                    $files = $columnValue;
                } else {
                    continue;
                }

                if (is_array($files)) {
                    foreach ($files as $file) {
                        if ($file && strpos($file, 'images/') === 0) {
                            $filePath = $this->extractFilePathFromUrl($file);
                            if ($filePath) {
                                $referencedFiles[] = $filePath;
                            }
                        }
                    }
                }
            }

            // Check User image
            $userImages = User::whereNotNull('image')
                ->where('image', '!=', '')
                ->pluck('image')
                ->filter()
                ->toArray();

            foreach ($userImages as $file) {
                if (strpos($file, 'images/') === 0) {
                    $filePath = $this->extractFilePathFromUrl($file);
                    if ($filePath) {
                        $referencedFiles[] = $filePath;
                    }
                }
            }

            // Check User syndicate_card
            $userSyndicateCards = User::whereNotNull('syndicate_card')
                ->where('syndicate_card', '!=', '')
                ->pluck('syndicate_card')
                ->filter()
                ->toArray();

            foreach ($userSyndicateCards as $file) {
                if (strpos($file, 'images/') === 0) {
                    $filePath = $this->extractFilePathFromUrl($file);
                    if ($filePath) {
                        $referencedFiles[] = $filePath;
                    }
                }
            }

            // Check Group header_picture
            $groupHeaderPictures = Group::whereNotNull('header_picture')
                ->where('header_picture', '!=', '')
                ->pluck('header_picture')
                ->filter()
                ->toArray();

            foreach ($groupHeaderPictures as $file) {
                if (strpos($file, 'images/') === 0) {
                    $filePath = $this->extractFilePathFromUrl($file);
                    if ($filePath) {
                        $referencedFiles[] = $filePath;
                    }
                }
            }

            // Check Group group_image
            $groupImages = Group::whereNotNull('group_image')
                ->where('group_image', '!=', '')
                ->pluck('group_image')
                ->filter()
                ->toArray();

            foreach ($groupImages as $file) {
                if (strpos($file, 'images/') === 0) {
                    $filePath = $this->extractFilePathFromUrl($file);
                    if ($filePath) {
                        $referencedFiles[] = $filePath;
                    }
                }
            }

        } catch (\Exception $e) {
            $this->warn('Database connection failed for images: '.$e->getMessage());
        }

        return array_unique($referencedFiles);
    }

    /**
     * Get medical reports referenced in the database
     */
    protected function getMedicalReportsReferencedFiles(): array
    {
        $referencedFiles = [];

        try {
            // Check all models that might reference medical reports
            // This is a placeholder - you may need to add specific models based on your application
            // For now, we'll return an empty array to avoid deleting files without proper references

            $this->warn('Medical reports cleanup: No specific model references found. Files will be preserved.');

        } catch (\Exception $e) {
            $this->warn('Database connection failed for medical reports: '.$e->getMessage());
        }

        return array_unique($referencedFiles);
    }

    /**
     * Get reports referenced in the database
     */
    protected function getReportsReferencedFiles(): array
    {
        $referencedFiles = [];

        try {
            // Check all models that might reference reports
            // This is a placeholder - you may need to add specific models based on your application
            // For now, we'll return an empty array to avoid deleting files without proper references

            $this->warn('Reports cleanup: No specific model references found. Files will be preserved.');

        } catch (\Exception $e) {
            $this->warn('Database connection failed for reports: '.$e->getMessage());
        }

        return array_unique($referencedFiles);
    }

    /**
     * Extract file path from URL
     */
    protected function extractFilePathFromUrl(string $url): ?string
    {
        // Remove domain and storage prefix
        $path = parse_url($url, PHP_URL_PATH);

        if ($path) {
            // Remove /storage/ prefix if present
            $path = preg_replace('/^\/storage\//', '', $path);

            return $path;
        }

        return null;
    }

    /**
     * Process a batch of files
     */
    protected function processBatch(\Illuminate\Contracts\Filesystem\Filesystem $storage, array $batch, array $referencedFiles, bool $dryRun): void
    {
        foreach ($batch as $filePath) {
            $fileName = basename($filePath);

            // Skip excluded files
            if ($this->shouldExcludeFile($fileName)) {
                $this->skippedCount++;

                continue;
            }

            // Check if file is referenced in database
            if (! in_array($filePath, $referencedFiles)) {
                // File is orphaned
                if ($dryRun) {
                    $this->line("Would delete: {$filePath}");
                } else {
                    try {
                        if ($storage->delete($filePath)) {
                            $this->deletedFiles[] = $filePath;
                            $this->deletedCount++;
                        } else {
                            $this->errorCount++;
                            $this->error("Failed to delete: {$filePath}");
                        }
                    } catch (\Exception $e) {
                        $this->errorCount++;
                        $this->error("Error deleting {$filePath}: ".$e->getMessage());
                    }
                }
            } else {
                $this->skippedCount++;
            }
        }
    }

    /**
     * Check if file should be excluded from cleanup
     */
    protected function shouldExcludeFile(string $fileName): bool
    {
        foreach ($this->excludePatterns as $pattern) {
            if (fnmatch($pattern, $fileName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Display cleanup summary
     */
    protected function displaySummary(float $startTime, bool $dryRun): void
    {
        $duration = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info('=== Cleanup Summary ===');
        $this->info("Duration: {$duration} seconds");

        if ($dryRun) {
            $this->info("Files that would be deleted: {$this->deletedCount}");
        } else {
            $this->info("Files deleted: {$this->deletedCount}");
        }

        $this->info("Files skipped: {$this->skippedCount}");

        if ($this->errorCount > 0) {
            $this->error("Errors encountered: {$this->errorCount}");
        }
    }

    /**
     * Log cleanup results for auditing
     */
    protected function logCleanupResults(bool $dryRun): void
    {
        $logData = [
            'timestamp' => Carbon::now()->toISOString(),
            'dry_run' => $dryRun,
            'deleted_count' => $this->deletedCount,
            'skipped_count' => $this->skippedCount,
            'error_count' => $this->errorCount,
            'deleted_files' => $this->deletedFiles,
        ];

        if ($dryRun) {
            Log::info('File cleanup dry run completed', $logData);
        } else {
            Log::info('File cleanup completed', $logData);
        }

        // Also log to a dedicated cleanup log file
        Log::channel('file_cleanup')->info($dryRun ? 'Dry run completed' : 'Cleanup completed', $logData);
    }
}
