<?php

namespace App\Filament\Resources\FeedPostCommentLikeResource\Pages;

use App\Filament\Resources\FeedPostCommentLikeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListFeedPostCommentLikes extends ListRecords
{
    protected static string $resource = FeedPostCommentLikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('feed_post_comment_likes_count');
    }
}
