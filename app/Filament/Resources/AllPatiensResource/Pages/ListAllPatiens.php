<?php

namespace App\Filament\Resources\AllPatiensResource\Pages;

use App\Filament\Resources\AllPatiensResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAllPatiens extends ListRecords
{
    protected static string $resource = AllPatiensResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
