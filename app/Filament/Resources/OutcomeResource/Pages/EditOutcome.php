<?php

namespace App\Filament\Resources\OutcomeResource\Pages;

use App\Filament\Resources\OutcomeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOutcome extends EditRecord
{
    protected static string $resource = OutcomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
