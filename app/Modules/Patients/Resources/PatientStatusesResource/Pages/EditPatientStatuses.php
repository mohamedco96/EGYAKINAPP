<?php

namespace App\Modules\Patients\Resources\PatientStatusesResource\Pages;

use App\Modules\Patients\Resources\PatientStatusesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPatientStatuses extends EditRecord
{
    protected static string $resource = PatientStatusesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
