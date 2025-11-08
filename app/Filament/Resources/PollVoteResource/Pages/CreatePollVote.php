<?php

namespace App\Filament\Resources\PollVoteResource\Pages;

use App\Filament\Resources\PollVoteResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreatePollVote extends CreateRecord
{
    protected static string $resource = PollVoteResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('poll_votes_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}