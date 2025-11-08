<?php

namespace App\Filament\Resources\FcmTokenResource\Pages;

use App\Filament\Resources\FcmTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListFcmTokens extends ListRecords
{
    protected static string $resource = FcmTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('fcm_tokens_count');
    }
}