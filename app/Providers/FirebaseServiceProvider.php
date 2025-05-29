<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Messaging as FirebaseMessaging;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FirebaseMessaging::class, function ($app) {
            $factory = (new Factory)
                ->withServiceAccount(base_path('storage/egyakin-firebase-service-account.json'));

            return $factory->createMessaging();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
