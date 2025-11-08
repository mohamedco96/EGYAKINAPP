<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPermission extends ViewRecord
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->getRecord())
            ->schema([
                Section::make('Permission Information')
                    ->icon('heroicon-o-key')
                    ->description('Permission details and metadata')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Permission Name')
                            ->size('lg')
                            ->weight('bold')
                            ->color('primary')
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['-', '_'], ' ', $state))),

                        TextEntry::make('guard_name')
                            ->label('Guard Name')
                            ->badge()
                            ->color('secondary'),

                        TextEntry::make('category')
                            ->label('Category')
                            ->badge()
                            ->color('warning')
                            ->placeholder('Uncategorized')
                            ->formatStateUsing(fn (?string $state): string => $state ? ucwords(str_replace(['-', '_'], ' ', $state)) : 'Uncategorized'),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime()
                            ->since(),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime()
                            ->since(),

                        TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('No description provided')
                            ->columnSpanFull(),
                    ])->columns(3),

                Section::make('Assigned Roles')
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('Roles with this Permission')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->limitList(10)
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['-', '_'], ' ', $state))),
                    ]),

                Section::make('Usage Statistics')
                    ->icon('heroicon-o-chart-pie')
                    ->description('Permission assignment and usage data')
                    ->schema([
                        TextEntry::make('roles_count')
                            ->label('Assigned to Roles')
                            ->numeric()
                            ->badge()
                            ->color('success'),

                        TextEntry::make('users_count')
                            ->label('Direct User Assignments')
                            ->numeric()
                            ->badge()
                            ->color('info'),

                        TextEntry::make('total_users_with_permission')
                            ->label('Total Users with Permission')
                            ->state(fn ($state, $record) => $record->users()->count() + $record->roles()->withCount('users')->get()->sum('users_count'))
                            ->numeric()
                            ->badge()
                            ->color('warning'),
                    ])->columns(3),
            ]);
    }
}
