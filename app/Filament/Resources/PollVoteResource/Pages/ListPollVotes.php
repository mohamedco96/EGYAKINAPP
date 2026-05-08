<?php

namespace App\Filament\Resources\PollVoteResource\Pages;

use App\Filament\Resources\PollVoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListPollVotes extends ListRecords
{
    protected static string $resource = PollVoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('poll_votes_count');
    }
}
