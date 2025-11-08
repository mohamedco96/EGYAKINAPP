<?php

namespace App\Filament\Resources\ConsultationReplyResource\Pages;

use App\Filament\Resources\ConsultationReplyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewConsultationReply extends ViewRecord
{
    protected static string $resource = ConsultationReplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}