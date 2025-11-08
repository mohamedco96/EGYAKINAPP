<?php

namespace App\Filament\Resources\ConsultationDoctorResource\Pages;

use App\Filament\Resources\ConsultationDoctorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListConsultationDoctors extends ListRecords
{
    protected static string $resource = ConsultationDoctorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('consultation_doctors_count');
    }
}