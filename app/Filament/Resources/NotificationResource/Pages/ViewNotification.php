<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Cache;

class ViewNotification extends ViewRecord
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('markAsRead')
                ->label('Mark as Read')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => ! $record->read)
                ->action(function ($record) {
                    $record->update(['read' => true]);
                    Cache::forget('notifications_unread_count');
                })
                ->successNotificationTitle('Notification marked as read'),
            Actions\Action::make('markAsUnread')
                ->label('Mark as Unread')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->visible(fn ($record) => $record->read)
                ->action(function ($record) {
                    $record->update(['read' => false]);
                    Cache::forget('notifications_unread_count');
                })
                ->successNotificationTitle('Notification marked as unread'),
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    Cache::forget('notifications_count');
                    Cache::forget('notifications_unread_count');
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->getRecord())
            ->schema([
                Infolists\Components\Section::make('Notification Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('Notification ID')
                                    ->badge()
                                    ->color('gray'),

                                Infolists\Components\TextEntry::make('type')
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

                                Infolists\Components\TextEntry::make('read')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Read' : 'Unread')
                                    ->color(fn ($state) => $state ? 'success' : 'warning')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime()
                                    ->icon('heroicon-o-clock'),
                            ]),

                        Infolists\Components\TextEntry::make('content')
                            ->label('Content')
                            ->columnSpanFull()
                            ->prose()
                            ->markdown(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Related Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('doctor.name')
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

                                Infolists\Components\TextEntry::make('doctor.email')
                                    ->label('Doctor Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage('Email copied!')
                                    ->copyMessageDuration(1500),

                                Infolists\Components\TextEntry::make('patient_id')
                                    ->label('Patient ID')
                                    ->badge()
                                    ->prefix('#')
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('type_id')
                                    ->label('Type ID')
                                    ->badge()
                                    ->visible(fn ($state, $record) => ! empty($record->type_id)),
                            ]),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make('Localization')
                    ->schema([
                        Infolists\Components\TextEntry::make('localization_key')
                            ->label('Localization Key')
                            ->copyable()
                            ->placeholder('Not set'),

                        Infolists\Components\KeyValueEntry::make('localization_params')
                            ->label('Localization Parameters')
                            ->placeholder('No parameters'),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($state, $record) => ! empty($record->localization_key) || ! empty($record->localization_params)),

                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-calendar'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Updated')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-pencil'),

                                Infolists\Components\TextEntry::make('type_doctor_id')
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
