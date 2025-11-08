<?php

namespace App\Filament\Resources\AIConsultationResource\Pages;

use App\Filament\Resources\AIConsultationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditAIConsultation extends EditRecord
{
    protected static string $resource = AIConsultationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    Cache::forget('ai_consultations_count');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
