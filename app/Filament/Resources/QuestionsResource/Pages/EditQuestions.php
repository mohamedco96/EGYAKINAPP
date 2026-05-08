<?php

namespace App\Filament\Resources\QuestionsResource\Pages;

use App\Filament\Resources\QuestionsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuestions extends EditRecord
{
    protected static string $resource = QuestionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
