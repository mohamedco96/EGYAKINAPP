<?php

namespace App\Filament\Resources\PostCommentsResource\Pages;

use App\Filament\Resources\PostCommentsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditPostComments extends EditRecord
{
    protected static string $resource = PostCommentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    Cache::forget('post_commentss_count');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}