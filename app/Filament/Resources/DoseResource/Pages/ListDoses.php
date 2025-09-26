<?php

namespace App\Filament\Resources\DoseResource\Pages;

use App\Filament\Resources\DoseResource;
use App\Filament\Widgets\DoseStatsWidget;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListDoses extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = DoseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Dose Modifier')
                ->icon('heroicon-m-plus')
                ->color('primary')
                ->createAnother(false),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DoseStatsWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Dose Modifiers';
    }

    public function getSubheading(): ?string
    {
        return 'Manage medication dosing guidelines and modifications';
    }
}
