<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function update(array $data)
    {
        // Fetch the current record
        $record = $this->record;

        // Preserve existing values for profile_image and syndicate_card if not set
        $data['image'] = isset($data['image']) ? $data['image'] : $record->image;
        $data['syndicate_card'] = isset($data['syndicate_card']) ? $data['syndicate_card'] : $record->syndicate_card;

        // Perform the update
        DB::transaction(function () use ($data) {
            $this->record->update($data);
        });

        // Optionally handle file uploads here if needed
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * After saving, mark the user as having permissions changed if their
     * roles or direct permissions were modified via the Filament form.
     */
    protected function afterSave(): void
    {
        $user = $this->record->fresh(['roles', 'permissions']);

        // Spatie syncs roles/permissions via pivot — compare current vs original
        $originalRoleIds = $this->record->getOriginal('roles') ?? null;

        // Always set permissions_changed when roles or permissions are part of the saved form data
        // The CheckboxList relationship components sync roles/permissions on save
        $formData = $this->data;

        $hasRolesField = array_key_exists('roles', $formData);
        $hasPermissionsField = array_key_exists('permissions', $formData);

        if ($hasRolesField || $hasPermissionsField) {
            $user->update(['permissions_changed' => true]);
        }
    }
}
