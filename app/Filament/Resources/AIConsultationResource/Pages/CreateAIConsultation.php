<?php

namespace App\Filament\Resources\AIConsultationResource\Pages;

use App\Filament\Resources\AIConsultationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateAIConsultation extends CreateRecord
{
    protected static string $resource = AIConsultationResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('ai_consultations_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
