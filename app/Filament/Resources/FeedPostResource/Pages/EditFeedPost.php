<?php

namespace App\Filament\Resources\FeedPostResource\Pages;

use App\Filament\Resources\FeedPostResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditFeedPost extends EditRecord
{
    protected static string $resource = FeedPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->after(function () {
                    Cache::forget('feed_posts_count');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
