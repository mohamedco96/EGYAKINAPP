<?php

namespace App\Filament\Resources\HashtagResource\Pages;

use App\Filament\Resources\HashtagResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateHashtag extends CreateRecord
{
    protected static string $resource = HashtagResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('hashtags_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}