<?php

namespace App\Filament\Resources\AllPatiensResource\Pages;

use App\Filament\Resources\AllPatiensResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAllPatiens extends ListRecords
{
    protected static string $resource = AllPatiensResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
