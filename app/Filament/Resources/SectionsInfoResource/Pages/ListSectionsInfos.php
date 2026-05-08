<?php

namespace App\Filament\Resources\SectionsInfoResource\Pages;

use App\Filament\Resources\SectionsInfoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSectionsInfos extends ListRecords
{
    protected static string $resource = SectionsInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
