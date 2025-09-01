<?php

namespace App\Providers;

use App\Models\FeedPost;
use App\Models\User;
use App\Observers\FeedPostObserver;
use App\Observers\UserObserver;
use App\Services\FileCleanupService;
use Illuminate\Support\ServiceProvider;

class FileCleanupServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the FileCleanupService as a singleton
        $this->app->singleton(FileCleanupService::class, function ($app) {
            return new FileCleanupService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register model observers only if cleanup is enabled
        if (config('filesystems.cleanup.enabled', true)) {
            // Register observers for automatic file cleanup on model changes
            FeedPost::observe(FeedPostObserver::class);
            User::observe(UserObserver::class);

            // You can add more model observers here as needed
            // Example:
            // SomeOtherModel::observe(SomeOtherModelObserver::class);
        }
    }
}

