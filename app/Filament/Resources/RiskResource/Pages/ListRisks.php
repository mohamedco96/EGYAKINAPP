<?php

namespace App\Filament\Resources\RiskResource\Pages;

use App\Filament\Resources\RiskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRisks extends ListRecords
{
    protected static string $resource = RiskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
