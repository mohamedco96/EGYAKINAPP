<?php

namespace App\Filament\Resources\AIConsultationResource\Pages;

use App\Filament\Resources\AIConsultationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAIConsultation extends ViewRecord
{
    protected static string $resource = AIConsultationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
