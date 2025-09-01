<?php

namespace App\Console\Commands;

use App\Models\FeedPost;
use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ValidateCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cleanup:validate';

    /**
     * The console command description.
     */
    protected $description = 'Validate if the cleanup process is working correctly';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Cleanup Process Validation');
        $this->info('============================');
        $this->newLine();

        $this->checkScheduledJobs();
        $this->checkLogFiles();
        $this->checkFileSystem();
        $this->checkDatabaseReferences();
        $this->checkConfiguration();

        $this->newLine();
        $this->info('✅ Validation Complete!');

        return self::SUCCESS;
    }

    private function checkScheduledJobs(): void
    {
        $this->info('📅 Checking Scheduled Jobs:');

        $output = shell_exec('php artisan schedule:list 2>/dev/null');

        if (strpos($output, 'files:cleanup') !== false) {
            $this->line('   ✅ Cleanup job is scheduled');
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (strpos($line, 'files:cleanup') !== false) {
                    $this->line('   📋 Schedule: '.trim($line));
                }
            }
        } else {
            $this->error('   ❌ Cleanup job not found in schedule');
        }

        $this->newLine();
    }

    private function checkLogFiles(): void
    {
        $this->info('📊 Checking Log Files:');

        $logFiles = [
            'storage/logs/file_cleanup-*.log',
            'storage/logs/scheduled_cleanup.log',
            'storage/logs/laravel.log',
        ];

        foreach ($logFiles as $pattern) {
            $files = glob($pattern);
            if (! empty($files)) {
                $latestFile = end($files);
                $lastModified = date('Y-m-d H:i:s', filemtime($latestFile));
                $this->line("   📄 {$pattern}: Last modified {$lastModified}");

                // Show last few lines
                $lastLines = shell_exec("tail -3 {$latestFile} 2>/dev/null");
                if ($lastLines) {
                    $this->line('   📝 Recent entries:');
                    foreach (explode("\n", trim($lastLines)) as $line) {
                        if (trim($line)) {
                            $this->line('      '.trim($line));
                        }
                    }
                }
            } else {
                $this->warn("   ⚠️  {$pattern}: No log files found");
            }
        }

        $this->newLine();
    }

    private function checkFileSystem(): void
    {
        $this->info('📁 Checking File System:');

        $storage = Storage::disk('public');
        $directories = [
            'images',
            'media_images',
            'media_videos',
            'profile_images',
            'syndicate_card',
            'header_pictures',
            'group_images',
            'medical_reports',
            'reports',
        ];

        foreach ($directories as $dir) {
            if ($storage->exists($dir)) {
                $files = $storage->allFiles($dir);
                $count = count($files);
                $this->line("   📂 {$dir}: {$count} files");
            } else {
                $this->line("   📂 {$dir}: Directory does not exist");
            }
        }

        // Check root files
        $rootFiles = $storage->files();
        $this->line('   📂 root: '.count($rootFiles).' files');

        $this->newLine();
    }

    private function checkDatabaseReferences(): void
    {
        $this->info('🗄️  Checking Database References:');

        try {
            // FeedPost references
            $feedPosts = FeedPost::whereNotNull('media_path')
                ->where('media_path', '!=', '[]')
                ->where('media_path', '!=', '')
                ->count();
            $this->line("   📝 FeedPost media_path: {$feedPosts} records");

            // User image references
            $userImages = User::whereNotNull('image')
                ->where('image', '!=', '')
                ->count();
            $this->line("   👤 User images: {$userImages} records");

            // User syndicate card references
            $userSyndicateCards = User::whereNotNull('syndicate_card')
                ->where('syndicate_card', '!=', '')
                ->count();
            $this->line("   🆔 User syndicate cards: {$userSyndicateCards} records");

            // Group image references
            $groupImages = Group::whereNotNull('header_picture')
                ->where('header_picture', '!=', '')
                ->count();
            $this->line("   👥 Group header pictures: {$groupImages} records");

            $groupGroupImages = Group::whereNotNull('group_image')
                ->where('group_image', '!=', '')
                ->count();
            $this->line("   🖼️  Group images: {$groupGroupImages} records");

        } catch (\Exception $e) {
            $this->error('   ❌ Database connection failed: '.$e->getMessage());
        }

        $this->newLine();
    }

    private function checkConfiguration(): void
    {
        $this->info('⚙️  Checking Configuration:');

        $config = config('filesystems.cleanup');

        $this->line('   🔧 Cleanup enabled: '.($config['enabled'] ? 'Yes' : 'No'));
        $this->line('   🔧 Auto cleanup on delete: '.($config['auto_cleanup_on_delete'] ? 'Yes' : 'No'));
        $this->line('   🔧 Scheduled cleanup enabled: '.($config['schedule']['enabled'] ? 'Yes' : 'No'));
        $this->line('   🔧 Frequency: '.$config['schedule']['frequency']);
        $this->line('   🔧 Time: '.$config['schedule']['time']);
        $this->line('   🔧 Disk: '.$config['schedule']['disk']);
        $this->line('   🔧 Batch size: '.$config['schedule']['batch_size']);
        $this->line('   🔧 Retention days: '.$config['retention_days']);

        $this->newLine();
    }
}
