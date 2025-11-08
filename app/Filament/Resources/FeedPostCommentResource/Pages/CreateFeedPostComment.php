<?php

namespace App\Filament\Resources\FeedPostCommentResource\Pages;

use App\Filament\Resources\FeedPostCommentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateFeedPostComment extends CreateRecord
{
    protected static string $resource = FeedPostCommentResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('feed_post_comments_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}