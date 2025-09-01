<?php

namespace App\Console\Commands;

use App\Models\FeedPost;
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
                            {--batch-size=100 : Number of files to process at once}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up orphaned files that no longer have matching database records';

    /**
     * File patterns to check for cleanup
     */
    protected array $filePatterns = [
        'media_images' => [
            'model' => FeedPost::class,
            'column' => 'media_path',
            'type' => 'json_array',
        ],
        'profile_images' => [
            'model' => User::class,
            'column' => 'image',
            'type' => 'string',
        ],
        // Add more patterns as needed
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
     * Get files referenced in the database
     */
    protected function getReferencedFiles(array $config): array
    {
        $model = $config['model'];
        $column = $config['column'];
        $type = $config['type'];

        $referencedFiles = [];

        if ($type === 'json_array') {
            // Handle JSON array columns (like media_path)
            $records = $model::whereNotNull($column)
                ->where($column, '!=', '[]')
                ->where($column, '!=', '')
                ->select('id', $column)
                ->get();

            foreach ($records as $record) {
                $files = json_decode($record->{$column}, true);
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

