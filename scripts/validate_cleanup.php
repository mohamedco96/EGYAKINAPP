<?php

/**
 * Cleanup Process Validation Script
 *
 * This script helps validate if the file cleanup process is working correctly
 * on your server.
 */

require_once __DIR__.'/../vendor/autoload.php';

use Illuminate\Support\Facades\Storage;

class CleanupValidator
{
    public function run()
    {
        echo "🔍 Cleanup Process Validation\n";
        echo "============================\n\n";

        $this->checkScheduledJobs();
        $this->checkLogFiles();
        $this->checkFileSystem();
        $this->checkDatabaseReferences();
        $this->checkConfiguration();

        echo "\n✅ Validation Complete!\n";
    }

    private function checkScheduledJobs()
    {
        echo "📅 Checking Scheduled Jobs:\n";

        $output = shell_exec('php artisan schedule:list 2>/dev/null');

        if (strpos($output, 'files:cleanup') !== false) {
            echo "   ✅ Cleanup job is scheduled\n";
            echo '   📋 Schedule: '.trim($output)."\n";
        } else {
            echo "   ❌ Cleanup job not found in schedule\n";
        }

        echo "\n";
    }

    private function checkLogFiles()
    {
        echo "📊 Checking Log Files:\n";

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
                echo "   📄 {$pattern}: Last modified {$lastModified}\n";

                // Show last few lines
                $lastLines = shell_exec("tail -5 {$latestFile} 2>/dev/null");
                if ($lastLines) {
                    echo "   📝 Recent entries:\n";
                    foreach (explode("\n", trim($lastLines)) as $line) {
                        if (trim($line)) {
                            echo '      '.trim($line)."\n";
                        }
                    }
                }
            } else {
                echo "   ⚠️  {$pattern}: No log files found\n";
            }
        }

        echo "\n";
    }

    private function checkFileSystem()
    {
        echo "📁 Checking File System:\n";

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
                echo "   📂 {$dir}: {$count} files\n";
            } else {
                echo "   📂 {$dir}: Directory does not exist\n";
            }
        }

        // Check root files
        $rootFiles = $storage->files();
        echo '   📂 root: '.count($rootFiles)." files\n";

        echo "\n";
    }

    private function checkDatabaseReferences()
    {
        echo "🗄️  Checking Database References:\n";

        try {
            // FeedPost references
            $feedPosts = \App\Models\FeedPost::whereNotNull('media_path')
                ->where('media_path', '!=', '[]')
                ->where('media_path', '!=', '')
                ->count();
            echo "   📝 FeedPost media_path: {$feedPosts} records\n";

            // User image references
            $userImages = \App\Models\User::whereNotNull('image')
                ->where('image', '!=', '')
                ->count();
            echo "   👤 User images: {$userImages} records\n";

            // User syndicate card references
            $userSyndicateCards = \App\Models\User::whereNotNull('syndicate_card')
                ->where('syndicate_card', '!=', '')
                ->count();
            echo "   🆔 User syndicate cards: {$userSyndicateCards} records\n";

            // Group image references
            $groupImages = \App\Models\Group::whereNotNull('header_picture')
                ->where('header_picture', '!=', '')
                ->count();
            echo "   👥 Group header pictures: {$groupImages} records\n";

            $groupGroupImages = \App\Models\Group::whereNotNull('group_image')
                ->where('group_image', '!=', '')
                ->count();
            echo "   🖼️  Group images: {$groupGroupImages} records\n";

        } catch (Exception $e) {
            echo '   ❌ Database connection failed: '.$e->getMessage()."\n";
        }

        echo "\n";
    }

    private function checkConfiguration()
    {
        echo "⚙️  Checking Configuration:\n";

        $config = config('filesystems.cleanup');

        echo '   🔧 Cleanup enabled: '.($config['enabled'] ? 'Yes' : 'No')."\n";
        echo '   🔧 Auto cleanup on delete: '.($config['auto_cleanup_on_delete'] ? 'Yes' : 'No')."\n";
        echo '   🔧 Scheduled cleanup enabled: '.($config['schedule']['enabled'] ? 'Yes' : 'No')."\n";
        echo '   🔧 Frequency: '.$config['schedule']['frequency']."\n";
        echo '   🔧 Time: '.$config['schedule']['time']."\n";
        echo '   🔧 Disk: '.$config['schedule']['disk']."\n";
        echo '   🔧 Batch size: '.$config['schedule']['batch_size']."\n";
        echo '   🔧 Retention days: '.$config['retention_days']."\n";

        echo "\n";
    }
}

// Run validation
$validator = new CleanupValidator();
$validator->run();
