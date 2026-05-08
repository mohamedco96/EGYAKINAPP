<?php

namespace App\Modules\Patients\Resources\PatientStatusesResource\Pages;

use App\Filament\Widgets\SectionStatusStatsWidget;
use App\Modules\Patients\Resources\PatientStatusesResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListPatientStatuses extends ListRecords
{
    protected static string $resource = PatientStatusesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearCache')
                ->label('Clear Cache')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    Cache::forget('section_status_stats');
                    Cache::flush();

                    Notification::make()
                        ->success()
                        ->title('Cache Cleared')
                        ->body('Section status cache has been cleared successfully.')
                        ->send();
                }),

        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SectionStatusStatsWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Patient Sections Status';
    }

    public function getHeading(): string
    {
        return 'Patient Sections Status';
    }

    public function getSubheading(): ?string
    {
        return 'Monitor patient section completion progress and manage section statuses.';
    }
}
