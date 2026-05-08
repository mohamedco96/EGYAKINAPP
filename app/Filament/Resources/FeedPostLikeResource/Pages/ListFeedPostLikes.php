<?php

namespace App\Filament\Resources\FeedPostLikeResource\Pages;

use App\Filament\Resources\FeedPostLikeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListFeedPostLikes extends ListRecords
{
    protected static string $resource = FeedPostLikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('feed_post_likes_count');
    }
}
