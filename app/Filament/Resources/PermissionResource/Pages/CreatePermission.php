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
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Permission Created Successfully')
            ->body("The permission '{$this->getRecord()->name}' has been created and assigned to {$this->getRecord()->roles()->count()} roles.")
            ->duration(5000);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure permission name is in lowercase with proper format
        $data['name'] = strtolower(str_replace(' ', '-', $data['name']));

        return $data;
    }

    public function getTitle(): string
    {
        return 'Create New Permission';
    }

    public function getSubheading(): ?string
    {
        return 'Define a new permission and assign it to roles';
    }
}
