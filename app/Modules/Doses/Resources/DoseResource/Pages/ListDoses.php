<?php

namespace App\Modules\Doses\Resources\DoseResource\Pages;

use App\Modules\Doses\Resources\DoseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDoses extends ListRecords
{
    protected static string $resource = DoseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
