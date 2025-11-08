<?php

namespace App\Filament\Resources\ConsultationDoctorResource\Pages;

use App\Filament\Resources\ConsultationDoctorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditConsultationDoctor extends EditRecord
{
    protected static string $resource = ConsultationDoctorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    Cache::forget('consultation_doctors_count');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}