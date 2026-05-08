<?php

namespace App\Modules\Patients\Resources\PatientsResource\Pages;

use App\Modules\Patients\Resources\PatientsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPatients extends EditRecord
{
    protected static string $resource = PatientsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
