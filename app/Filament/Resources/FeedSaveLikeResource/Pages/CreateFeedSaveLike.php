<?php

namespace App\Filament\Resources\FeedSaveLikeResource\Pages;

use App\Filament\Resources\FeedSaveLikeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateFeedSaveLike extends CreateRecord
{
    protected static string $resource = FeedSaveLikeResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('feed_save_likes_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}