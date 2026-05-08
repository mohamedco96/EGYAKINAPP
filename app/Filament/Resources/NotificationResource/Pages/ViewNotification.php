<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;

class ViewNotification extends ViewRecord
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAsRead')
                ->label('Mark as Read')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => ! $record->read)
                ->action(function ($record) {
                    $record->update(['read' => true]);
                    Cache::forget('notifications_unread_count');
                })
                ->successNotificationTitle('Notification marked as read'),
            Action::make('markAsUnread')
                ->label('Mark as Unread')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->visible(fn ($record) => $record->read)
                ->action(function ($record) {
                    $record->update(['read' => false]);
                    Cache::forget('notifications_unread_count');
                })
                ->successNotificationTitle('Notification marked as unread'),
            EditAction::make(),
            DeleteAction::make()
                ->after(function () {
                    Cache::forget('notifications_count');
                    Cache::forget('notifications_unread_count');
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $infolist
            ->record($this->getRecord())
            ->schema([
                Section::make('Notification Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Notification ID')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('type')
                                    ->label('Type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Consultation' => 'info',
                                        'Recommendation' => 'success',
                                        'Patient' => 'warning',
                                        'Score' => 'primary',
                                        'System' => 'gray',
                                        default => 'secondary',
                                    })
                                    ->icon(fn (string $state): string => match ($state) {
                                        'Consultation' => 'heroicon-o-chat-bubble-left-right',
                                        'Recommendation' => 'heroicon-o-light-bulb',
                                        'Patient' => 'heroicon-o-user',
                                        'Score' => 'heroicon-o-chart-bar',
                                        'System' => 'heroicon-o-cog',
                                        default => 'heroicon-o-bell',
                                    }),

                                TextEntry::make('read')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Read' : 'Unread')
                                    ->color(fn ($state) => $state ? 'success' : 'warning')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),

                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime()
                                    ->icon('heroicon-o-clock'),
                            ]),

                        TextEntry::make('content')
                            ->label('Content')
                            ->columnSpanFull()
                            ->prose()
                            ->markdown(),
                    ])
                    ->columns(2),

                Section::make('Related Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('doctor.name')
                                    ->label('Doctor')
                                    ->formatStateUsing(fn ($state, $record) => $record->doctor
                                            ? $record->doctor->name.' '.$record->doctor->lname
                                            : 'N/A'
                                    )
                                    ->icon('heroicon-o-user-circle')
                                    ->url(fn ($state, $record) => $record->doctor_id
                                            ? route('filament.admin.resources.users.edit', ['record' => $record->doctor_id])
                                            : null
                                    )
                                    ->color('primary'),

                                TextEntry::make('doctor.email')
                                    ->label('Doctor Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage('Email copied!')
                                    ->copyMessageDuration(1500),

                                TextEntry::make('patient_id')
                                    ->label('Patient ID')
                                    ->badge()
                                    ->prefix('#')
                                    ->color('primary'),

                                TextEntry::make('type_id')
                                    ->label('Type ID')
                                    ->badge()
                                    ->visible(fn ($state, $record) => ! empty($record->type_id)),
                            ]),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Localization')
                    ->schema([
                        TextEntry::make('localization_key')
                            ->label('Localization Key')
                            ->copyable()
                            ->placeholder('Not set'),

                        KeyValueEntry::make('localization_params')
                            ->label('Localization Parameters')
                            ->placeholder('No parameters'),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($state, $record) => ! empty($record->localization_key) || ! empty($record->localization_params)),

                Section::make('Metadata')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-calendar'),

                                TextEntry::make('updated_at')
                                    ->label('Updated')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-pencil'),

                                TextEntry::make('type_doctor_id')
                                    ->label('Type Doctor ID')
                                    ->badge()
                                    ->visible(fn ($state, $record) => ! empty($record->type_doctor_id)),
                            ]),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
