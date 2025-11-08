<?php

namespace App\Filament\Resources\ConsultationDoctorResource\Pages;

use App\Filament\Resources\ConsultationDoctorResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewConsultationDoctor extends ViewRecord
{
    protected static string $resource = ConsultationDoctorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}