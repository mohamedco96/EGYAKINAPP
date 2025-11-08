<?php

namespace App\Filament\Resources\DoctorMonthlyTrialResource\Pages;

use App\Filament\Resources\DoctorMonthlyTrialResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateDoctorMonthlyTrial extends CreateRecord
{
    protected static string $resource = DoctorMonthlyTrialResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('doctor_monthly_trials_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}