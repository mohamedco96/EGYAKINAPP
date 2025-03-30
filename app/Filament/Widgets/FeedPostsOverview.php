<?php

namespace App\Filament\Widgets;

use App\Models\FeedPost;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FeedPostsOverview extends BaseWidget
{
    protected static ?int $sort = 6;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Posts', FeedPost::count())
                ->description('Total number of feed posts')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
            
            Stat::make('Posts with Media', FeedPost::whereNotNull('media_path')->count())
                ->description('Posts containing media')
                ->descriptionIcon('heroicon-m-photo')
                ->color('success'),
            
            Stat::make('Group Posts', FeedPost::whereNotNull('group_id')->count())
                ->description('Posts in groups')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
        ];
    }
} 