<?php

namespace App\Console\Commands;

use App\Modules\Notifications\Services\FcmTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupFcmTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:cleanup {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old and invalid FCM tokens from the database';

    /**
     * Execute the console command.
     */
    public function handle(FcmTokenService $fcmTokenService): int
    {
        $this->info('Starting FCM token cleanup...');

        try {
            // Clean up old tokens (older than 6 months)
            $deletedCount = $fcmTokenService->cleanupInvalidTokens();

            $this->info("Successfully cleaned up {$deletedCount} old FCM tokens.");

            Log::info('FCM token cleanup completed', [
                'deleted_count' => $deletedCount,
                'executed_by' => 'console_command',
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to cleanup FCM tokens: '.$e->getMessage());

            Log::error('FCM token cleanup failed', [
                'error' => $e->getMessage(),
                'executed_by' => 'console_command',
            ]);

            return self::FAILURE;
        }
    }
}
