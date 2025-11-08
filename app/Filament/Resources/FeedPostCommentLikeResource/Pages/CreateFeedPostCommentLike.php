<?php

namespace App\Filament\Resources\FeedPostCommentLikeResource\Pages;

use App\Filament\Resources\FeedPostCommentLikeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateFeedPostCommentLike extends CreateRecord
{
    protected static string $resource = FeedPostCommentLikeResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('feed_post_comment_likes_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}