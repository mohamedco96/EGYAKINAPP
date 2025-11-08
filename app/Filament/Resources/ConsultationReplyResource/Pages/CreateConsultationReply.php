<?php

namespace App\Filament\Resources\ConsultationReplyResource\Pages;

use App\Filament\Resources\ConsultationReplyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateConsultationReply extends CreateRecord
{
    protected static string $resource = ConsultationReplyResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('consultation_replys_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}