<?php

namespace App\Filament\Resources\AnswersResource\Pages;

use App\Filament\Resources\AnswersResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateAnswers extends CreateRecord
{
    protected static string $resource = AnswersResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('answerss_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}