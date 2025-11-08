<?php

namespace App\Filament\Resources\ConsultationDoctorResource\Pages;

use App\Filament\Resources\ConsultationDoctorResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateConsultationDoctor extends CreateRecord
{
    protected static string $resource = ConsultationDoctorResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('consultation_doctors_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}