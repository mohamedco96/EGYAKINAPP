<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Permission Created')
            ->body('The permission has been created successfully.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure permission name is in lowercase with proper format
        $data['name'] = strtolower(str_replace(' ', '-', $data['name']));

        return $data;
    }
}
