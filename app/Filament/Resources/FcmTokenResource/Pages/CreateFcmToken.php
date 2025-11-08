<?php

namespace App\Filament\Resources\FcmTokenResource\Pages;

use App\Filament\Resources\FcmTokenResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateFcmToken extends CreateRecord
{
    protected static string $resource = FcmTokenResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('fcm_tokens_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}