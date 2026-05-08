<?php

namespace App\Filament\Resources\AIConsultationResource\Pages;

use App\Filament\Resources\AIConsultationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListAIConsultations extends ListRecords
{
    protected static string $resource = AIConsultationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('ai_consultations_count');
    }
}
