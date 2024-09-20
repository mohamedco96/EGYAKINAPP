<?php

namespace App\Filament\Resources\PatientStatusesResource\Pages;

use App\Filament\Resources\PatientStatusesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPatientStatuses extends ListRecords
{
    protected static string $resource = PatientStatusesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
