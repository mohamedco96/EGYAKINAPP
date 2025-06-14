<?php

namespace App\Modules\Patients\Resources\PatientStatusesResource\Pages;

use App\Modules\Patients\Resources\PatientStatusesResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePatientStatuses extends CreateRecord
{
    protected static string $resource = PatientStatusesResource::class;
}
