<?php

namespace App\Filament\Resources\FcmTokenResource\Pages;

use App\Filament\Resources\FcmTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFcmToken extends ViewRecord
{
    protected static string $resource = FcmTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}