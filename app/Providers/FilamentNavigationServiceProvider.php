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
            // Define navigation groups with emoji prefixes (no icons per Filament UX guidelines)
            // Individual resources have icons, so groups use emojis for visual distinction
            Filament::registerNavigationGroups([
                NavigationGroup::make('🏠 Dashboard')
                    ->collapsed(false),

                NavigationGroup::make('🔐 Access Control')
                    ->collapsed(false),

                NavigationGroup::make('👨‍⚕️ Medical Team')
                    ->collapsed(false),

                NavigationGroup::make('🏥 Patient Management')
                    ->collapsed(true),

                NavigationGroup::make('📊 Medical Data')
                    ->collapsed(true),

                NavigationGroup::make('📝 Content Management')
                    ->collapsed(true),

                NavigationGroup::make('📢 Communications')
                    ->collapsed(true),

                NavigationGroup::make('⚙️ System Settings')
                    ->collapsed(true),
            ]);

            // Add custom navigation items
            Filament::registerNavigationItems([
                NavigationItem::make('Dashboard')
                    ->url('/admin')
                    ->icon('heroicon-o-home')
                    ->group('🏠 Dashboard')
                    ->sort(1),
            ]);
        });
    }
}
