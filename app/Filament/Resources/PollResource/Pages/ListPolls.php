<?php

namespace App\Filament\Resources\PollResource\Pages;

use App\Filament\Resources\PollResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListPolls extends ListRecords
{
    protected static string $resource = PollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('polls_count');
    }
}
