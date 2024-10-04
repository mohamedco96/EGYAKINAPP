<?php

namespace App\Filament\Resources\SectionsInfoResource\Pages;

use App\Filament\Resources\SectionsInfoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSectionsInfos extends ListRecords
{
    protected static string $resource = SectionsInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
