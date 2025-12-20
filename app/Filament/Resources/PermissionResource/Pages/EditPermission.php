<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPermission extends EditRecord
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Permission Updated')
            ->body('The permission has been updated successfully.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure permission name is in lowercase with proper format
        $data['name'] = strtolower(str_replace(' ', '-', $data['name']));

        return $data;
    }

    /**
     * After saving permission, mark all users with assigned roles as having permissions changed
     */
    protected function afterSave(): void
    {
        $permission = $this->record;
        $permission->refresh(); // Refresh to get latest role assignments
        
        // Mark all users with roles that have this permission as having permissions changed
        foreach ($permission->roles as $role) {
            User::role($role->name)->update(['permissions_changed' => true]);
        }
    }
}
