<?php

namespace App\Filament\Resources\PostCommentsResource\Pages;

use App\Filament\Resources\PostCommentsResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPostComments extends ViewRecord
{
    protected static string $resource = PostCommentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
