<?php

namespace App\Filament\Resources\FeedPostResource\Pages;

use App\Filament\Resources\FeedPostResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFeedPost extends ViewRecord
{
    protected static string $resource = FeedPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
