<?php

namespace App\Filament\Resources\UserAchievementResource\Pages;

use App\Filament\Resources\UserAchievementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListUserAchievements extends ListRecords
{
    protected static string $resource = UserAchievementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('user_achievements_count');
    }
}
