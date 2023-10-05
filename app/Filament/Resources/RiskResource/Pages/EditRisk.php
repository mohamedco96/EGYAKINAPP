<?php

namespace App\Filament\Resources\RiskResource\Pages;

use App\Filament\Resources\RiskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRisk extends EditRecord
{
    protected static string $resource = RiskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
