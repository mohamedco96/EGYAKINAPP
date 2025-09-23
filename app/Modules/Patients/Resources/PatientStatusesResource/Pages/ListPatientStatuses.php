<?php

namespace App\Modules\Patients\Resources\PatientStatusesResource\Pages;

use App\Filament\Widgets\SectionStatusStatsWidget;
use App\Modules\Patients\Resources\PatientStatusesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListPatientStatuses extends ListRecords
{
    protected static string $resource = PatientStatusesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('clearCache')
                ->label('Clear Cache')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    Cache::forget('section_status_stats');
                    Cache::flush();

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Cache Cleared')
                        ->body('Section status cache has been cleared successfully.')
                        ->send();
                }),

            Actions\Action::make('refreshStats')
                ->label('Refresh Statistics')
                ->icon('heroicon-o-chart-bar-square')
                ->color('info')
                ->action(function () {
                    Cache::forget('section_status_stats');

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Statistics Refreshed')
                        ->body('Section statistics have been refreshed.')
                        ->send();

                    return redirect()->to(request()->url());
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
