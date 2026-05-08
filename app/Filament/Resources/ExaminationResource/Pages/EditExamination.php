<?php

namespace App\Filament\Resources\ExaminationResource\Pages;

use App\Filament\Resources\ExaminationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExamination extends EditRecord
{
    protected static string $resource = ExaminationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
