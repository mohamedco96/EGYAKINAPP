<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Facades\Cache;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('block')
                ->label(fn ($record) => $record->blocked ? 'Unblock' : 'Block')
                ->icon(fn ($record) => $record->blocked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                ->color(fn ($record) => $record->blocked ? 'success' : 'danger')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['blocked' => ! $record->blocked]);
                })
                ->successNotificationTitle(fn ($record) => $record->blocked ? 'User blocked' : 'User unblocked'),
            EditAction::make(),
            DeleteAction::make()
                ->after(function () {
                    Cache::forget('users_count');
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('User ID')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('blocked')
                                    ->label('Account Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Blocked' : 'Active')
                                    ->color(fn ($state) => $state ? 'danger' : 'success')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-lock-closed' : 'heroicon-o-check-circle'),

                                TextEntry::make('limited')
                                    ->label('Access Level')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Limited' : 'Full Access')
                                    ->color(fn ($state) => $state ? 'warning' : 'success')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                ImageEntry::make('image')
                                    ->label('Profile Image')
                                    ->circular()
                                    ->height(150),

                                ImageEntry::make('syndicate_card')
                                    ->label('Syndicate Card')
                                    ->height(150)
                                    ->visible(fn ($state, $record) => ! empty($record->syndicate_card)),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Full Name')
                                    ->formatStateUsing(fn ($state, $record) => $record->name.' '.$record->lname)
                                    ->size(TextSize::Large)
                                    ->weight('bold')
                                    ->icon('heroicon-o-user'),

                                TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage('Email copied!')
                                    ->copyMessageDuration(1500),

                                TextEntry::make('phone')
                                    ->label('Phone')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->placeholder('Not provided'),

                                TextEntry::make('age')
                                    ->label('Age')
                                    ->suffix(' years')
                                    ->icon('heroicon-o-calendar')
                                    ->placeholder('Not provided'),

                                TextEntry::make('gender')
                                    ->label('Gender')
                                    ->badge()
                                    ->color(fn ($state) => $state === 'Male' ? 'info' : 'success')
                                    ->icon('heroicon-o-user'),
                            ]),
                    ])
                    ->columns(3),

                Section::make('Professional Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('specialty')
                                    ->label('Specialty')
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-academic-cap')
                                    ->placeholder('Not specified'),

                                TextEntry::make('job')
                                    ->label('Job Title')
                                    ->icon('heroicon-o-briefcase')
                                    ->placeholder('Not specified'),

                                TextEntry::make('workingplace')
                                    ->label('Working Place')
                                    ->icon('heroicon-o-building-office')
                                    ->placeholder('Not specified'),

                                TextEntry::make('highestdegree')
                                    ->label('Highest Degree')
                                    ->icon('heroicon-o-academic-cap')
                                    ->placeholder('Not specified'),

                                TextEntry::make('registration_number')
                                    ->label('Registration Number')
                                    ->icon('heroicon-o-identification')
                                    ->copyable()
                                    ->placeholder('Not provided'),

                                TextEntry::make('isSyndicateCardRequired')
                                    ->label('Syndicate Card Status')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'Verified' => 'success',
                                        'Pending' => 'warning',
                                        'Required' => 'danger',
                                        default => 'gray',
                                    })
                                    ->icon(fn ($state) => match ($state) {
                                        'Verified' => 'heroicon-o-check-circle',
                                        'Pending' => 'heroicon-o-clock',
                                        'Required' => 'heroicon-o-exclamation-triangle',
                                        default => 'heroicon-o-question-mark-circle',
                                    }),
                            ]),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Statistics')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Total Patients')
                                    ->state(fn ($record) => $record->patients->count())
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-o-users'),

                                TextEntry::make('id')
                                    ->label('Total Posts')
                                    ->state(fn ($record) => $record->posts->count())
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-o-document-text'),

                                TextEntry::make('id')
                                    ->label('Score Records')
                                    ->state(fn ($record) => $record->score->count())
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-trophy'),
                            ]),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Roles & Permissions')
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('Assigned Roles')
                            ->badge()
                            ->color('success')
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['-', '_'], ' ', $state)))
                            ->placeholder('No roles assigned'),

                        TextEntry::make('permissions.name')
                            ->label('Direct Permissions')
                            ->badge()
                            ->color('info')
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['-', '_'], ' ', $state)))
                            ->placeholder('No direct permissions'),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),

                Section::make('Account Timeline')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Registered')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-calendar')
                                    ->tooltip(fn ($state, $record) => $record->created_at?->format('M d, Y H:i:s')),

                                TextEntry::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-check-circle')
                                    ->placeholder('Not verified')
                                    ->tooltip(fn ($state, $record) => $record->email_verified_at?->format('M d, Y H:i:s')),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-pencil')
                                    ->tooltip(fn ($state, $record) => $record->updated_at?->format('M d, Y H:i:s')),
                            ]),

                        TextEntry::make('created_at')
                            ->label('Account Age')
                            ->formatStateUsing(function ($state, $record) {
                                $diff = $record->created_at->diff(now());
                                if ($diff->days > 0) {
                                    return $diff->days.' days';
                                }
                                if ($diff->h > 0) {
                                    return $diff->h.' hours';
                                }

                                return $diff->i.' minutes';
                            })
                            ->badge()
                            ->color(function ($state, $record) {
                                $days = $record->created_at->diffInDays(now());
                                if ($days < 30) {
                                    return 'success';
                                }
                                if ($days < 365) {
                                    return 'warning';
                                }

                                return 'info';
                            })
                            ->icon('heroicon-o-clock'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
