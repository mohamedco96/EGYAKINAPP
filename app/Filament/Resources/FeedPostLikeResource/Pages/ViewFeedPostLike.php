<?php

namespace App\Filament\Resources\FeedPostLikeResource\Pages;

use App\Filament\Resources\FeedPostLikeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFeedPostLike extends ViewRecord
{
    protected static string $resource = FeedPostLikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}