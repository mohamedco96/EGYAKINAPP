<?php

namespace App\Modules\Patients\Resources\PatientsResource\Pages;

use App\Modules\Patients\Resources\PatientsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPatients extends ListRecords
{
    protected static string $resource = PatientsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
