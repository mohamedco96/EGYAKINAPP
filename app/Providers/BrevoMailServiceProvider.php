<?php

namespace App\Providers;

use App\Notifications\Channels\BrevoApiChannel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
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
        // Register Brevo API notification channel
        Notification::extend('brevo-api', function ($app) {
            return new BrevoApiChannel();
        });

        // Use log driver for brevo-api mailer to avoid transport issues
        Mail::extend('brevo-api', function (array $config) {
            return app('mail.manager')->createTransport('log');
        });
    }
}
