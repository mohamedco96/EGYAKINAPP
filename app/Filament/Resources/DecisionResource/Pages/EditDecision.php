<?php

namespace App\Filament\Resources\DecisionResource\Pages;

use App\Filament\Resources\DecisionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDecision extends EditRecord
{
    protected static string $resource = DecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
