<?php

namespace App\Filament\Resources\ScoreHistoryResource\Pages;

use App\Filament\Resources\ScoreHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScoreHistories extends ListRecords
{
    protected static string $resource = ScoreHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
