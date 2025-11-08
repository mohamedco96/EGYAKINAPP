<?php

namespace App\Filament\Resources\FeedSaveLikeResource\Pages;

use App\Filament\Resources\FeedSaveLikeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFeedSaveLike extends ViewRecord
{
    protected static string $resource = FeedSaveLikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}