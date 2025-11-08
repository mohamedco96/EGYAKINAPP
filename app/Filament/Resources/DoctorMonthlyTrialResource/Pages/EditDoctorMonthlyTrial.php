<?php

namespace App\Filament\Resources\DoctorMonthlyTrialResource\Pages;

use App\Filament\Resources\DoctorMonthlyTrialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditDoctorMonthlyTrial extends EditRecord
{
    protected static string $resource = DoctorMonthlyTrialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    Cache::forget('doctor_monthly_trials_count');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}