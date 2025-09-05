<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

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
                Section::make('Role Information')
                    ->icon('heroicon-o-identification')
                    ->description('Basic role details and metadata')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Role Name')
                            ->size('lg')
                            ->weight('bold')
                            ->color('primary')
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['-', '_'], ' ', $state))),

                        TextEntry::make('guard_name')
                            ->label('Guard Name')
                            ->badge()
                            ->color('secondary'),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime()
                            ->since(),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime()
                            ->since(),
                    ])->columns(2),

                Section::make('Permissions')
                    ->schema([
                        TextEntry::make('permissions.name')
                            ->label('Assigned Permissions')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->limitList(10)
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['-', '_'], ' ', $state))),
                    ]),

                Section::make('Statistics & Usage')
                    ->icon('heroicon-o-chart-bar')
                    ->description('Role usage and assignment statistics')
                    ->schema([
                        TextEntry::make('permissions_count')
                            ->label('Total Permissions')
                            ->numeric()
                            ->badge()
                            ->color('success'),

                        TextEntry::make('users_count')
                            ->label('Users with this Role')
                            ->numeric()
                            ->badge()
                            ->color('info'),

                        TextEntry::make('users.name')
                            ->label('Assigned Users')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->limitList(5)
                            ->placeholder('No users assigned')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
