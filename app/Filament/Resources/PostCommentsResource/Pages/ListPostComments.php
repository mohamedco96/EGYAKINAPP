<?php

namespace App\Filament\Resources\PostCommentsResource\Pages;

use App\Filament\Resources\PostCommentsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListPostComments extends ListRecords
{
    protected static string $resource = PostCommentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('post_comments_count');
    }
}
