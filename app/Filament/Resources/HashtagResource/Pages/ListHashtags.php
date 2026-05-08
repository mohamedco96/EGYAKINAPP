<?php

namespace App\Filament\Resources\HashtagResource\Pages;

use App\Filament\Resources\HashtagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListHashtags extends ListRecords
{
    protected static string $resource = HashtagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('hashtags_count');
    }
}
