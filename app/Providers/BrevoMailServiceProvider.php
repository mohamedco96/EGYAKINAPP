<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class BrevoMailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Use log driver for brevo-api to avoid transport issues
        Mail::extend('brevo-api', function (array $config) {
            return app('mail.manager')->createTransport('log');
        });
    }
}
