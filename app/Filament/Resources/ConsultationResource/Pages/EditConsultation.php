<?php

namespace App\Filament\Resources\ConsultationResource\Pages;

use App\Filament\Resources\ConsultationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditConsultation extends EditRecord
{
    protected static string $resource = ConsultationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->after(function () {
                    Cache::forget('consultations_count');
                    Cache::forget('consultations_open_count');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
