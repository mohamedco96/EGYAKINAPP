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
                NavigationGroup::make('🏥 Patient Management')
                    ->collapsible()
                    ->collapsed(false),

                NavigationGroup::make('👥 User Management')
                    ->collapsible()
                    ->collapsed(false),

                NavigationGroup::make('📊 Medical Data')
                    ->collapsible()
                    ->collapsed(true),

                NavigationGroup::make('App Data')
                    ->collapsible()
                    ->collapsed(true),

                NavigationGroup::make('📝 Content Management')
                    ->collapsible()
                    ->collapsed(true),

                NavigationGroup::make('💬 AI & Consultations')
                    ->collapsible()
                    ->collapsed(true),

                NavigationGroup::make('📱 Social Feed')
                    ->collapsible()
                    ->collapsed(true),

                NavigationGroup::make('📢 Communications')
                    ->collapsible()
                    ->collapsed(true),

                NavigationGroup::make('🔐 Access Control')
                    ->collapsible()
                    ->collapsed(true),

                NavigationGroup::make('🔒 System Administration')
                    ->collapsible()
                    ->collapsed(true),
            ]);
        });
    }
}
