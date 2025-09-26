<?php

namespace App\Filament\Resources\DoseResource\Pages;

use App\Filament\Resources\DoseResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDose extends EditRecord
{
    protected static string $resource = DoseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->color('info')
                ->icon('heroicon-m-eye'),
            Actions\DeleteAction::make()
                ->color('danger')
                ->icon('heroicon-m-trash')
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to delete this dose modifier? This action cannot be undone.'),
            Actions\ReplicateAction::make()
                ->color('success')
                ->icon('heroicon-m-square-2-stack')
                ->form([
                    \Filament\Forms\Components\TextInput::make('title')
                        ->label('New Title')
                        ->required()
                        ->default(fn ($record) => $record->title.' (Copy)'),
                ])
                ->beforeReplicaSaved(function (array $data, $record): void {
                    $data['title'] = $data['title'] ?? $record->title.' (Copy)';
                }),
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
            ->title('Dose modifier updated')
            ->body('The dose modifier has been updated successfully.')
            ->icon('heroicon-o-check-circle');
    }

    public function getTitle(): string
    {
        return 'Edit Dose Modifier';
    }

    public function getSubheading(): ?string
    {
        return 'Update medication dosing guideline';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // You can add any data mutations here before saving
        $data['updated_at'] = now();

        return $data;
    }
}
