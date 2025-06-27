<?php

namespace App\Providers;
use App\Models\FeedPost;
use App\Policies\FeedPostPolicy;
use App\Modules\Settings\Models\Settings;
use App\Modules\Settings\Policies\SettingsPolicy;
// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        FeedPost::class => FeedPostPolicy::class,
        Settings::class => SettingsPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
