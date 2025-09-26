<?php

namespace App\Filament\Resources\DoseResource\Pages;

use App\Filament\Resources\DoseResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateDose extends CreateRecord
{
    protected static string $resource = DoseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Dose modifier created')
            ->body('The dose modifier has been created successfully.')
            ->icon('heroicon-o-check-circle');
    }

    public function getTitle(): string
    {
        return 'Create Dose Modifier';
    }

    public function getSubheading(): ?string
    {
        return 'Add a new medication dosing guideline';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // You can add any data mutations here before creating
        return $data;
    }
}
