<?php

namespace App\Filament\Resources\FeedPostCommentResource\Pages;

use App\Filament\Resources\FeedPostCommentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditFeedPostComment extends EditRecord
{
    protected static string $resource = FeedPostCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->after(function () {
                    Cache::forget('feed_post_comments_count');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
