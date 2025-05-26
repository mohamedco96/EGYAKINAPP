<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use PDO;

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
        // Force debug mode off in production
        if (App::environment('production')) {
            Config::set('app.debug', false);
        }

        // Ensure secure session configuration
        if (App::environment('production')) {
            Config::set('session.secure', true);
            Config::set('session.same_site', 'lax');
        }

        // Ensure secure cookie configuration
        if (App::environment('production')) {
            Config::set('session.cookie_secure', true);
            Config::set('session.cookie_httponly', true);
        }

        // Ensure proper database SSL configuration
        if (App::environment('production')) {
            Config::set('database.connections.mysql.ssl', true);
            Config::set('database.connections.mysql.options', [
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
            ]);
        }
    }
}
