<?php

namespace App\Filament\Resources\FeedPostLikeResource\Pages;

use App\Filament\Resources\FeedPostLikeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateFeedPostLike extends CreateRecord
{
    protected static string $resource = FeedPostLikeResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('feed_post_likes_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}