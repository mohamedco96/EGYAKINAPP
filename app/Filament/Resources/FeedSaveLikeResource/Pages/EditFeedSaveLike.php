<?php

namespace App\Filament\Resources\FeedSaveLikeResource\Pages;

use App\Filament\Resources\FeedSaveLikeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditFeedSaveLike extends EditRecord
{
    protected static string $resource = FeedSaveLikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    Cache::forget('feed_save_likes_count');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}