<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Cache;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('block')
                ->label(fn ($record) => $record->blocked ? 'Unblock' : 'Block')
                ->icon(fn ($record) => $record->blocked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                ->color(fn ($record) => $record->blocked ? 'success' : 'danger')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['blocked' => !$record->blocked]);
                })
                ->successNotificationTitle(fn ($record) => $record->blocked ? 'User blocked' : 'User unblocked'),
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    Cache::forget('users_count');
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->getRecord())
            ->schema([
                Infolists\Components\Section::make('Personal Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('User ID')
                                    ->badge()
                                    ->color('gray'),

                                Infolists\Components\TextEntry::make('blocked')
                                    ->label('Account Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Blocked' : 'Active')
                                    ->color(fn ($state) => $state ? 'danger' : 'success')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-lock-closed' : 'heroicon-o-check-circle'),

                                Infolists\Components\TextEntry::make('limited')
                                    ->label('Access Level')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Limited' : 'Full Access')
                                    ->color(fn ($state) => $state ? 'warning' : 'success')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle'),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\ImageEntry::make('image')
                                    ->label('Profile Image')
                                    ->circular()
                                    ->height(150),

                                Infolists\Components\ImageEntry::make('syndicate_card')
                                    ->label('Syndicate Card')
                                    ->height(150)
                                    ->visible(fn ($state, $record) => !empty($record->syndicate_card)),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Full Name')
                                    ->formatStateUsing(fn ($state, $record) => $record->name . ' ' . $record->lname)
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->icon('heroicon-o-user'),

                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage('Email copied!')
                                    ->copyMessageDuration(1500),

                                Infolists\Components\TextEntry::make('phone')
                                    ->label('Phone')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->placeholder('Not provided'),

                                Infolists\Components\TextEntry::make('age')
                                    ->label('Age')
                                    ->suffix(' years')
                                    ->icon('heroicon-o-calendar')
                                    ->placeholder('Not provided'),

                                Infolists\Components\TextEntry::make('gender')
                                    ->label('Gender')
                                    ->badge()
                                    ->color(fn ($state) => $state === 'Male' ? 'info' : 'success')
                                    ->icon('heroicon-o-user'),
                            ]),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Professional Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('specialty')
                                    ->label('Specialty')
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-academic-cap')
                                    ->placeholder('Not specified'),

                                Infolists\Components\TextEntry::make('job')
                                    ->label('Job Title')
                                    ->icon('heroicon-o-briefcase')
                                    ->placeholder('Not specified'),

                                Infolists\Components\TextEntry::make('workingplace')
                                    ->label('Working Place')
                                    ->icon('heroicon-o-building-office')
                                    ->placeholder('Not specified'),

                                Infolists\Components\TextEntry::make('highestdegree')
                                    ->label('Highest Degree')
                                    ->icon('heroicon-o-academic-cap')
                                    ->placeholder('Not specified'),

                                Infolists\Components\TextEntry::make('registration_number')
                                    ->label('Registration Number')
                                    ->icon('heroicon-o-identification')
                                    ->copyable()
                                    ->placeholder('Not provided'),

                                Infolists\Components\TextEntry::make('isSyndicateCardRequired')
                                    ->label('Syndicate Card Status')
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        'Verified' => 'success',
                                        'Pending' => 'warning',
                                        'Required' => 'danger',
                                        default => 'gray',
                                    })
                                    ->icon(fn ($state) => match($state) {
                                        'Verified' => 'heroicon-o-check-circle',
                                        'Pending' => 'heroicon-o-clock',
                                        'Required' => 'heroicon-o-exclamation-triangle',
                                        default => 'heroicon-o-question-mark-circle',
                                    }),
                            ]),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('Total Patients')
                                    ->state(fn ($record) => $record->patients->count())
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-o-users'),

                                Infolists\Components\TextEntry::make('id')
                                    ->label('Total Posts')
                                    ->state(fn ($record) => $record->posts->count())
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-o-document-text'),

                                Infolists\Components\TextEntry::make('id')
                                    ->label('Score Records')
                                    ->state(fn ($record) => $record->score->count())
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-trophy'),
                            ]),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Infolists\Components\Section::make('Roles & Permissions')
                    ->schema([
                        Infolists\Components\TextEntry::make('roles.name')
                            ->label('Assigned Roles')
                            ->badge()
                            ->color('success')
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['-', '_'], ' ', $state)))
                            ->placeholder('No roles assigned'),

                        Infolists\Components\TextEntry::make('permissions.name')
                            ->label('Direct Permissions')
                            ->badge()
                            ->color('info')
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['-', '_'], ' ', $state)))
                            ->placeholder('No direct permissions'),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make('Account Timeline')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Registered')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-calendar')
                                    ->tooltip(fn ($state, $record) => $record->created_at?->format('M d, Y H:i:s')),

                                Infolists\Components\TextEntry::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-check-circle')
                                    ->placeholder('Not verified')
                                    ->tooltip(fn ($state, $record) => $record->email_verified_at?->format('M d, Y H:i:s')),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-pencil')
                                    ->tooltip(fn ($state, $record) => $record->updated_at?->format('M d, Y H:i:s')),
                            ]),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Account Age')
                            ->formatStateUsing(function ($state, $record) {
                                $diff = $record->created_at->diff(now());
                                if ($diff->days > 0) {
                                    return $diff->days . ' days';
                                }
                                if ($diff->h > 0) {
                                    return $diff->h . ' hours';
                                }
                                return $diff->i . ' minutes';
                            })
                            ->badge()
                            ->color(function ($state, $record) {
                                $days = $record->created_at->diffInDays(now());
                                if ($days < 30) return 'success';
                                if ($days < 365) return 'warning';
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
