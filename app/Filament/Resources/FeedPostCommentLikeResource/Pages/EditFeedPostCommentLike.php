<?php

namespace App\Filament\Resources\FeedPostCommentLikeResource\Pages;

use App\Filament\Resources\FeedPostCommentLikeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditFeedPostCommentLike extends EditRecord
{
    protected static string $resource = FeedPostCommentLikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    Cache::forget('feed_post_comment_likes_count');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}