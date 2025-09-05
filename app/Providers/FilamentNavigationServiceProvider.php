<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\ServiceProvider;

class FilamentNavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Filament::serving(function () {
            // Define navigation groups with icons and sorting
            Filament::registerNavigationGroups([
                NavigationGroup::make('🏠 Dashboard')
                    ->icon('heroicon-o-home')
                    ->collapsed(false),

                NavigationGroup::make('🔐 Access Control')
                    ->icon('heroicon-o-shield-check')
                    ->collapsed(false),

                NavigationGroup::make('👨‍⚕️ Medical Team')
                    ->icon('heroicon-o-users')
                    ->collapsed(false),

                NavigationGroup::make('🏥 Patient Management')
                    ->icon('heroicon-o-user-group')
                    ->collapsed(true),

                NavigationGroup::make('📊 Medical Data')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed(true),

                NavigationGroup::make('📝 Content Management')
                    ->icon('heroicon-o-document-text')
                    ->collapsed(true),

                NavigationGroup::make('📢 Communications')
                    ->icon('heroicon-o-bell')
                    ->collapsed(true),

                NavigationGroup::make('⚙️ System Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(true),
            ]);

            // Add custom navigation items
            Filament::registerNavigationItems([
                NavigationItem::make('Dashboard')
                    ->url('/admin')
                    ->icon('heroicon-o-home')
                    ->group('🏠 Dashboard')
                    ->sort(1),

                NavigationItem::make('Analytics')
                    ->url('/admin/analytics')
                    ->icon('heroicon-o-chart-pie')
                    ->group('🏠 Dashboard')
                    ->sort(2)
                    ->badge('New', 'success'),

                NavigationItem::make('System Health')
                    ->url('/admin/health')
                    ->icon('heroicon-o-heart')
                    ->group('⚙️ System Settings')
                    ->sort(10),

                NavigationItem::make('Backup & Restore')
                    ->url('/admin/backup')
                    ->icon('heroicon-o-server-stack')
                    ->group('⚙️ System Settings')
                    ->sort(20),
            ]);
        });
    }
}
