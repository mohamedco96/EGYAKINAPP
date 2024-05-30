<?php

namespace App\Filament\Widgets;

use App\Models\Patients;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PatientTypeOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Doctors', User::count())
            //->description('32k increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => "\$dispatch('setStatusFilter', { filter: 'processed' })",
                ]),
            Stat::make('Patients', Patients::count())
          //  ->description('32k increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            //Stat::make('Complaints', PatientHistory::count()),
            // Stat::make('Sections', PatientHistory::count()),
            // Stat::make('Rabbits', PatientHistory::query()->where('type', 'rabbit')->count()),
        ];
    }
}
