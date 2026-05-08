<?php

namespace App\Filament\Resources\FeedPostLikeResource\Pages;

use App\Filament\Resources\FeedPostLikeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditFeedPostLike extends EditRecord
{
    protected static string $resource = FeedPostLikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->after(function () {
                    Cache::forget('feed_post_likes_count');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
