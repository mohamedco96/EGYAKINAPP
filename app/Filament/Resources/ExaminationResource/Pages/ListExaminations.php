<?php

namespace App\Filament\Resources\ExaminationResource\Pages;

use App\Filament\Resources\ExaminationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExaminations extends ListRecords
{
    protected static string $resource = ExaminationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
