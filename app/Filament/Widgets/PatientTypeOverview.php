<?php

namespace App\Filament\Widgets;

use App\Models\PatientHistory;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PatientTypeOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Doctors', User::count()),
            Stat::make('Patients', PatientHistory::count()),
           // Stat::make('Rabbits', PatientHistory::query()->where('type', 'rabbit')->count()),
        ];
    }
}
