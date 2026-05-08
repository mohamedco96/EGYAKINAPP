<?php

namespace App\Modules\Achievements\Filament\Resources\AchievementResource\Pages;

use App\Modules\Achievements\Filament\Resources\AchievementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAchievements extends ListRecords
{
    protected static string $resource = AchievementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
