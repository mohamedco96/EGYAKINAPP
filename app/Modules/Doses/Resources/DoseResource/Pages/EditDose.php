<?php

namespace App\Modules\Doses\Resources\DoseResource\Pages;

use App\Modules\Doses\Resources\DoseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDose extends EditRecord
{
    protected static string $resource = DoseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
