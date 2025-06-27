<?php

namespace App\Modules\Achievements\Filament\Resources\AchievementResource\Pages;

use App\Modules\Achievements\Filament\Resources\AchievementResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAchievement extends CreateRecord
{
    protected static string $resource = AchievementResource::class;
}
