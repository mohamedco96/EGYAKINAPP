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
}
