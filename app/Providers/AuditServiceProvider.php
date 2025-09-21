<?php

namespace App\Providers;

use App\Helpers\AuditHelper;
use App\Models\FeedPost;
use App\Models\Score;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Services\AuditService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the audit service as singleton
        $this->app->singleton(AuditService::class, function ($app) {
            return new AuditService();
        });

        // Register audit helper
        $this->app->singleton('audit', function ($app) {
            return new AuditHelper();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register model observers for automatic auditing
        $this->registerModelObservers();

        // Register authentication event listeners
        $this->registerAuthEventListeners();

        // Register custom audit commands
        $this->registerAuditCommands();
    }

    /**
     * Register model observers for auditing.
     */
    protected function registerModelObservers(): void
    {
        // Get models that should be audited
        $auditableModels = $this->getAuditableModels();

        foreach ($auditableModels as $model) {
            if (class_exists($model)) {
                $model::observe(AuditObserver::class);
            }
        }
    }

    /**
     * Get list of models that should be audited.
     */
    protected function getAuditableModels(): array
    {
        return [
            // Core models
            User::class,
            Score::class,
            FeedPost::class,

            // Module models (check if they exist)
            \App\Modules\Patients\Models\Patients::class,
            \App\Modules\Posts\Models\Posts::class,
            \App\Modules\Posts\Models\PostComments::class,
            \App\Modules\Achievements\Models\Achievement::class,

            // Add other models as needed
            \App\Models\Group::class,
            \App\Models\Questions::class,
            \App\Models\Answers::class,
            \App\Models\SectionsInfo::class,
        ];
    }

    /**
     * Register authentication event listeners.
     */
    protected function registerAuthEventListeners(): void
    {
        // Listen for login events
        Event::listen(Login::class, function (Login $event) {
            AuditHelper::logLogin($event->user, [
                'guard' => $event->guard,
                'remember' => $event->remember,
            ]);
        });

        // Listen for logout events
        Event::listen(Logout::class, function (Logout $event) {
            AuditHelper::logLogout($event->user, [
                'guard' => $event->guard,
            ]);
        });

        // Listen for failed login attempts
        Event::listen(Failed::class, function (Failed $event) {
            AuditHelper::logFailedLogin($event->credentials['email'] ?? 'unknown', [
                'guard' => $event->guard,
                'credentials' => array_keys($event->credentials),
            ]);
        });

        // Listen for user registration
        Event::listen(Registered::class, function (Registered $event) {
            AuditHelper::log('user_registered', "New user registered: {$event->user->name}", [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
            ], $event->user);
        });

        // Listen for email verification
        Event::listen(Verified::class, function (Verified $event) {
            AuditHelper::logEmailVerified($event->user, [
                'verified_at' => now(),
            ]);
        });

        // Listen for password reset
        Event::listen(PasswordReset::class, function (PasswordReset $event) {
            AuditHelper::logPasswordReset($event->user, [
                'reset_at' => now(),
            ]);
        });
    }

    /**
     * Register custom audit commands.
     */
    protected function registerAuditCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\AuditCleanupCommand::class,
                \App\Console\Commands\AuditStatsCommand::class,
            ]);
        }
    }
}
