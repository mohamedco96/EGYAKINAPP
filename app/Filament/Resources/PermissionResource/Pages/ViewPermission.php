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
            ->schema([
                Section::make('Permission Information')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Permission Name')
                            ->size('lg')
                            ->weight('bold')
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['-', '_'], ' ', $state))),

                        TextEntry::make('guard_name')
                            ->label('Guard Name')
                            ->badge(),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),

                        TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime(),
                    ])->columns(2),

                Section::make('Assigned Roles')
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('Roles with this Permission')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->limitList(10)
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['-', '_'], ' ', $state))),
                    ]),

                Section::make('Statistics')
                    ->schema([
                        TextEntry::make('roles_count')
                            ->label('Total Roles')
                            ->numeric(),

                        TextEntry::make('users_count')
                            ->label('Direct Users')
                            ->numeric(),
                    ])->columns(2),
            ]);
    }
}
