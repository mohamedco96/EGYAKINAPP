<?php

namespace App\Filament\Resources\FeedPostCommentResource\Pages;

use App\Filament\Resources\FeedPostCommentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListFeedPostComments extends ListRecords
{
    protected static string $resource = FeedPostCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('feed_post_comments_count');
    }
}
