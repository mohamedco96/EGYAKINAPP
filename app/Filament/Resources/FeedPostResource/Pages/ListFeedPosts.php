<?php

namespace App\Filament\Resources\FeedPostResource\Pages;

use App\Filament\Resources\FeedPostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListFeedPosts extends ListRecords
{
    protected static string $resource = FeedPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('feed_posts_count');
    }
}
