<?php

namespace App\Filament\Resources\ConsultationReplyResource\Pages;

use App\Filament\Resources\ConsultationReplyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListConsultationReplies extends ListRecords
{
    protected static string $resource = ConsultationReplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('consultation_replies_count');
    }
}
