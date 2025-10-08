<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\ConsultationTrendsChart;
use App\Filament\Widgets\CoreMedicalOverview;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentActivityTable;
use App\Filament\Widgets\RecentConsultationsTable;
use App\Filament\Widgets\RolePermissionChartWidget;
use App\Modules\Achievements\Filament\Resources\AchievementResource;
use App\Modules\Doses\Resources\DoseResource;
use App\Modules\Patients\Resources\PatientsResource;
use App\Modules\Patients\Resources\PatientStatusesResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->resources([
                PatientsResource::class,
                PatientStatusesResource::class,
                AchievementResource::class,
                DoseResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                CoreMedicalOverview::class,
                ConsultationTrendsChart::class,
                RecentActivityTable::class,
                RecentConsultationsTable::class,
                QuickActionsWidget::class,
                RolePermissionChartWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
