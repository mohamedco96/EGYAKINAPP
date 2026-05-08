<?php

namespace App\Filament\Resources\PollOptionResource\Pages;

use App\Filament\Resources\PollOptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListPollOptions extends ListRecords
{
    protected static string $resource = PollOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('poll_options_count');
    }
}
