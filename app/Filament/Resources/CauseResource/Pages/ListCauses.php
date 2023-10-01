<?php

namespace App\Filament\Resources\CauseResource\Pages;

use App\Filament\Resources\CauseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCauses extends ListRecords
{
    protected static string $resource = CauseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
