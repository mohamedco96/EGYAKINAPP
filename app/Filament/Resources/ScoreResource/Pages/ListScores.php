<?php

namespace App\Filament\Resources\ScoreResource\Pages;

use App\Filament\Resources\ScoreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScores extends ListRecords
{
    protected static string $resource = ScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
