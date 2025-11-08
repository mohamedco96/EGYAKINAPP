<?php

namespace App\Filament\Resources\FeedPostCommentResource\Pages;

use App\Filament\Resources\FeedPostCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFeedPostComment extends ViewRecord
{
    protected static string $resource = FeedPostCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}