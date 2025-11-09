<?php

namespace App\Providers;

use App\Models\FeedPost;
use App\Models\Group;
use App\Models\User;
use App\Modules\Posts\Models\Posts;
use App\Observers\FeedPostObserver;
use App\Observers\GroupObserver;
use App\Observers\PostsObserver;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();

        // Register model observers for automatic file cleanup
        User::observe(UserObserver::class);
        FeedPost::observe(FeedPostObserver::class);
        Group::observe(GroupObserver::class);
        Posts::observe(PostsObserver::class);
    }
}
