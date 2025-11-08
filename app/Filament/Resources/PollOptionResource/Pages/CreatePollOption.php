<?php

namespace App\Filament\Resources\PollOptionResource\Pages;

use App\Filament\Resources\PollOptionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreatePollOption extends CreateRecord
{
    protected static string $resource = PollOptionResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('poll_options_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}