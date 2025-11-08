<?php

namespace App\Filament\Resources\UserAchievementResource\Pages;

use App\Filament\Resources\UserAchievementResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateUserAchievement extends CreateRecord
{
    protected static string $resource = UserAchievementResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('user_achievements_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}