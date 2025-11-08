<?php

namespace App\Filament\Resources\FeedPostCommentLikeResource\Pages;

use App\Filament\Resources\FeedPostCommentLikeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFeedPostCommentLike extends ViewRecord
{
    protected static string $resource = FeedPostCommentLikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}