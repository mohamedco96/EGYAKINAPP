<?php

namespace App\Modules\Patients\Resources\PatientsResource\Pages;

use App\Modules\Patients\Resources\PatientsResource;
use App\Filament\Widgets\PatientsStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListPatients extends ListRecords
{
    protected static string $resource = PatientsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // New patient button removed as requested
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PatientsStatsWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Patient Management';
    }

    public function getHeading(): string
    {
        return 'Patient Management';
    }

    public function getSubheading(): ?string
    {
        return 'Manage patient records, track progress, and monitor completion rates.';
    }
}
