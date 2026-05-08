<?php

namespace App\Modules\Achievements\Filament\Resources\AchievementResource\Pages;

use App\Modules\Achievements\Filament\Resources\AchievementResource;
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

class ViewAchievement extends ViewRecord
{
    protected static string $resource = AchievementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->after(function () {
                    Cache::forget('achievements_count');
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $infolist
            ->record($this->getRecord())
            ->schema([
                Section::make('Achievement Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Achievement ID')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('type')
                                    ->label('Type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'patient' => 'success',
                                        'score' => 'warning',
                                        'outcome' => 'info',
                                        default => 'gray',
                                    })
                                    ->icon(fn (string $state): string => match ($state) {
                                        'patient' => 'heroicon-o-user',
                                        'score' => 'heroicon-o-trophy',
                                        'outcome' => 'heroicon-o-chart-bar',
                                        default => 'heroicon-o-star',
                                    }),

                                TextEntry::make('score')
                                    ->label('Score')
                                    ->badge()
                                    ->size(TextSize::Large)
                                    ->color(fn ($state): string => match (true) {
                                        $state >= 100 => 'success',
                                        $state >= 50 => 'warning',
                                        default => 'info',
                                    })
                                    ->icon('heroicon-o-star'),
                            ]),

                        TextEntry::make('name')
                            ->label('Achievement Name')
                            ->columnSpanFull()
                            ->size(TextSize::Large)
                            ->weight('bold'),

                        ImageEntry::make('image')
                            ->label('Achievement Image')
                            ->columnSpanFull()
                            ->height(300),
                    ])
                    ->columns(3),

                Section::make('Description')
                    ->schema([
                        TextEntry::make('description')
                            ->label('')
                            ->columnSpanFull()
                            ->prose()
                            ->markdown()
                            ->placeholder('No description provided'),
                    ])
                    ->columns(1)
                    ->visible(fn ($state, $record) => ! empty($record->description)),

                Section::make('Statistics')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Users Earned')
                                    ->state(fn ($record) => $record->users ? $record->users->count() : 0)
                                    ->badge()

                                    ->color('success')
                                    ->icon('heroicon-o-user-group'),

                                TextEntry::make('score')
                                    ->label('Score Level')
                                    ->formatStateUsing(function ($state, $record) {
                                        return match (true) {
                                            $record->score >= 100 => 'High Value',
                                            $record->score >= 50 => 'Medium Value',
                                            default => 'Standard',
                                        };
                                    })
                                    ->badge()
                                    ->color(function ($state, $record) {
                                        return match (true) {
                                            $record->score >= 100 => 'success',
                                            $record->score >= 50 => 'warning',
                                            default => 'info',
                                        };
                                    }),

                                TextEntry::make('type_label')
                                    ->label('Category')
                                    ->formatStateUsing(function ($state, $record) {
                                        return match ($record->type) {
                                            'patient' => 'Patient-Based Achievement',
                                            'score' => 'Score-Based Achievement',
                                            'outcome' => 'Outcome-Based Achievement',
                                            default => 'General Achievement',
                                        };
                                    })
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Timeline')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-calendar')
                                    ->tooltip(fn ($state, $record) => $record->created_at?->format('M d, Y H:i:s')),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-pencil')
                                    ->tooltip(fn ($state, $record) => $record->updated_at?->format('M d, Y H:i:s')),

                                TextEntry::make('created_at')
                                    ->label('Age')
                                    ->formatStateUsing(function ($state, $record) {
                                        $diff = $record->created_at->diff(now());
                                        if ($diff->days > 0) {
                                            return $diff->days.' days old';
                                        }
                                        if ($diff->h > 0) {
                                            return $diff->h.' hours old';
                                        }

                                        return $diff->i.' minutes old';
                                    })
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-clock'),
                            ]),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
