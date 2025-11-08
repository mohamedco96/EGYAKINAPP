<?php

namespace App\Filament\Resources\FeedSaveLikeResource\Pages;

use App\Filament\Resources\FeedSaveLikeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListFeedSaveLikes extends ListRecords
{
    protected static string $resource = FeedSaveLikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('feed_save_likes_count');
    }
}