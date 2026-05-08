<?php

namespace App\Filament\Resources\UserAchievementResource\Pages;

use App\Filament\Resources\UserAchievementResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUserAchievement extends ViewRecord
{
    protected static string $resource = UserAchievementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
