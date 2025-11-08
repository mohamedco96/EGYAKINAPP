<?php

namespace App\Filament\Resources\DoctorMonthlyTrialResource\Pages;

use App\Filament\Resources\DoctorMonthlyTrialResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDoctorMonthlyTrial extends ViewRecord
{
    protected static string $resource = DoctorMonthlyTrialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}