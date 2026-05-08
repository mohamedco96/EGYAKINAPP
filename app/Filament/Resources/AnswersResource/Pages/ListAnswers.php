<?php

namespace App\Filament\Resources\AnswersResource\Pages;

use App\Filament\Resources\AnswersResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListAnswers extends ListRecords
{
    protected static string $resource = AnswersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('answers_count');
    }
}
