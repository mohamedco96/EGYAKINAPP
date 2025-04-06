<?php

namespace App\Filament\Widgets;

use App\Models\Group;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GroupsOverview extends BaseWidget
{
    protected static ?int $sort = 7;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Groups', Group::count())
                ->description('Total number of groups')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
            
            Stat::make('Public Groups', Group::where('privacy', 'public')->count())
                ->description('Public access groups')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('success'),
            
            Stat::make('Private Groups', Group::where('privacy', 'private')->count())
                ->description('Private access groups')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('warning'),
        ];
    }
} 