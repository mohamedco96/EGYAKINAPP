<?php

namespace App\Filament\Resources\PostCommentsResource\Pages;

use App\Filament\Resources\PostCommentsResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreatePostComments extends CreateRecord
{
    protected static string $resource = PostCommentsResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('post_commentss_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}