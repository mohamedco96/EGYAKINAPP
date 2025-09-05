<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Role Created Successfully')
            ->body("The role '{$this->getRecord()->name}' has been created with {$this->getRecord()->permissions()->count()} permissions.")
            ->duration(5000);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure role name is in proper format
        $data['name'] = strtolower(str_replace(' ', '-', $data['name']));

        return $data;
    }

    public function getTitle(): string
    {
        return 'Create New Role';
    }

    public function getSubheading(): ?string
    {
        return 'Define a new role and assign permissions';
    }
}
