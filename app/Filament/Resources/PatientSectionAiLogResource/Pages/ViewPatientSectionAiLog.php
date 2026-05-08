<?php

namespace App\Filament\Resources\PatientSectionAiLogResource\Pages;

use App\Filament\Resources\PatientSectionAiLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPatientSectionAiLog extends ViewRecord
{
    protected static string $resource = PatientSectionAiLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
