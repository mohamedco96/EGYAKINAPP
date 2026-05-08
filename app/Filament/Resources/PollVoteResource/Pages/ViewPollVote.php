<?php

namespace App\Filament\Resources\PollVoteResource\Pages;

use App\Filament\Resources\PollVoteResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPollVote extends ViewRecord
{
    protected static string $resource = PollVoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
