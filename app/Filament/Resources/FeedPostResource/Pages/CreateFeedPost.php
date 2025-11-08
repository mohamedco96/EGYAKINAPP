<?php

namespace App\Filament\Resources\FeedPostResource\Pages;

use App\Filament\Resources\FeedPostResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateFeedPost extends CreateRecord
{
    protected static string $resource = FeedPostResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('feed_posts_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
