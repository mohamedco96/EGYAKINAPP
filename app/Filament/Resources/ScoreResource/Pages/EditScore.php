<?php

namespace App\Filament\Resources\ScoreResource\Pages;

use App\Filament\Resources\ScoreResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScore extends EditRecord
{
    protected static string $resource = ScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
