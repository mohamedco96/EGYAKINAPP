<?php

namespace App\Filament\Resources\ConsultationReplyResource\Pages;

use App\Filament\Resources\ConsultationReplyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditConsultationReply extends EditRecord
{
    protected static string $resource = ConsultationReplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    Cache::forget('consultation_replys_count');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}