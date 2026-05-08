<?php

namespace App\Filament\Resources\DoctorMonthlyTrialResource\Pages;

use App\Filament\Resources\DoctorMonthlyTrialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListDoctorMonthlyTrials extends ListRecords
{
    protected static string $resource = DoctorMonthlyTrialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('doctor_monthly_trials_count');
    }
}
