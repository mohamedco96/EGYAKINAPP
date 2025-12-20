<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

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
            ->title('Role Updated')
            ->body('The role has been updated successfully.');
    }

    /**
     * After saving, mark all users with this role as having permissions changed
     * This catches permission syncs via Filament relationship form
     */
    protected function afterSave(): void
    {
        // Refresh the role to get latest permissions
        $this->record->refresh();
        
        // Mark all users with this role as having permissions changed
        User::role($this->record->name)->update(['permissions_changed' => true]);
    }
}
