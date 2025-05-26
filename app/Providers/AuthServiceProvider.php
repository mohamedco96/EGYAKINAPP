<?php

namespace App\Providers;

use App\Models\Assessment;
use App\Models\Decision;
use App\Models\Examination;
use App\Models\FeedPost;
use App\Models\Posts;
use App\Models\Questions;
use App\Models\Risk;
use App\Models\RolePermission;
use App\Models\Section;
use App\Models\SectionsInfo;
use App\Models\Settings;
use App\Policies\AssessmentPolicy;
use App\Policies\DecisionPolicy;
use App\Policies\ExaminationPolicy;
use App\Policies\FeedPostPolicy;
use App\Policies\PostsPolicy;
use App\Policies\QuestionsPolicy;
use App\Policies\RiskPolicy;
use App\Policies\RolePermissionPolicy;
use App\Policies\SectionPolicy;
use App\Policies\SectionsInfoPolicy;
use App\Policies\SettingsPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        FeedPost::class => FeedPostPolicy::class,
        Assessment::class => AssessmentPolicy::class,
        Risk::class => RiskPolicy::class,
        Section::class => SectionPolicy::class,
        Questions::class => QuestionsPolicy::class,
        Decision::class => DecisionPolicy::class,
        Posts::class => PostsPolicy::class,
        SectionsInfo::class => SectionsInfoPolicy::class,
        Examination::class => ExaminationPolicy::class,
        Settings::class => SettingsPolicy::class,
        RolePermission::class => RolePermissionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Register common gates
        Gate::define('manage-users', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('manage-roles', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('manage-permissions', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('view-dashboard', function ($user) {
            return $user->hasAnyRole(['admin', 'doctor']);
        });

        Gate::define('manage-patients', function ($user) {
            return $user->hasAnyRole(['admin', 'doctor']);
        });

        Gate::define('manage-posts', function ($user) {
            return $user->hasAnyRole(['admin', 'doctor']);
        });

        // Register policy gates
        $this->registerPolicies();
    }
}
