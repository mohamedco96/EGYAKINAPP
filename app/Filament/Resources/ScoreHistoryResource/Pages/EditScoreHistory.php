<?php

namespace App\Filament\Resources\ScoreHistoryResource\Pages;

use App\Filament\Resources\ScoreHistoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScoreHistory extends EditRecord
{
    protected static string $resource = ScoreHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
