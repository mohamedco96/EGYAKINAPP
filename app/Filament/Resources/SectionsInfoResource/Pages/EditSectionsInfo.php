<?php

namespace App\Filament\Resources\SectionsInfoResource\Pages;

use App\Filament\Resources\SectionsInfoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSectionsInfo extends EditRecord
{
    protected static string $resource = SectionsInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
