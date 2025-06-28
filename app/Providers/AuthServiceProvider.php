<?php

namespace App\Providers;
use App\Models\FeedPost;
use App\Policies\FeedPostPolicy;
use App\Modules\Settings\Models\Settings;
use App\Modules\Settings\Policies\SettingsPolicy;
use App\Modules\Chat\Models\AIConsultation;
use App\Modules\Chat\Models\DoctorMonthlyTrial;
use App\Modules\Chat\Policies\AIConsultationPolicy;
use App\Modules\Chat\Policies\DoctorMonthlyTrialPolicy;
use App\Modules\RolePermission\Models\RolePermission;
use App\Modules\RolePermission\Policies\RolePermissionPolicy;
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
        AIConsultation::class => AIConsultationPolicy::class,
        DoctorMonthlyTrial::class => DoctorMonthlyTrialPolicy::class,
        RolePermission::class => RolePermissionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
