<?php

namespace App\Filament\Resources\ConsultationResource\Pages;

use App\Filament\Resources\ConsultationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewConsultation extends ViewRecord
{
    protected static string $resource = ConsultationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
