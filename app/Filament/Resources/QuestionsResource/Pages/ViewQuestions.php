<?php

namespace App\Filament\Resources\QuestionsResource\Pages;

use App\Filament\Resources\QuestionsResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Facades\Cache;

class ViewQuestions extends ViewRecord
{
    protected static string $resource = QuestionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->after(function () {
                    Cache::forget('questions_count');
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $infolist
            ->record($this->getRecord())
            ->schema([
                Section::make('Question Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Question ID')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('type')
                                    ->label('Question Type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'string' => 'success',
                                        'select' => 'warning',
                                        'multiple' => 'info',
                                        'date' => 'primary',
                                        default => 'gray',
                                    })
                                    ->icon(fn (string $state): string => match ($state) {
                                        'string' => 'heroicon-o-pencil',
                                        'select' => 'heroicon-o-chevron-down',
                                        'multiple' => 'heroicon-o-queue-list',
                                        'date' => 'heroicon-o-calendar',
                                        default => 'heroicon-o-question-mark-circle',
                                    }),

                                TextEntry::make('sort')
                                    ->label('Sort Order')
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-arrows-up-down'),
                            ]),

                        TextEntry::make('question')
                            ->label('Question Text')
                            ->columnSpanFull()
                            ->size(TextSize::Large)
                            ->weight('bold'),
                    ])
                    ->columns(3),

                Section::make('Section Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('section_id')
                                    ->label('Section ID')
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('section_name')
                                    ->label('Section Name')
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-folder'),
                            ]),
                    ])
                    ->columns(2),

                Section::make('Configuration')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('mandatory')
                                    ->label('Mandatory')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Required' : 'Optional')
                                    ->color(fn ($state) => $state ? 'success' : 'gray')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),

                                TextEntry::make('hidden')
                                    ->label('Visibility')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Hidden' : 'Visible')
                                    ->color(fn ($state) => $state ? 'warning' : 'success')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-eye-slash' : 'heroicon-o-eye'),

                                TextEntry::make('keyboard_type')
                                    ->label('Keyboard Type')
                                    ->badge()
                                    ->color('secondary')
                                    ->icon('heroicon-o-computer-desktop')
                                    ->placeholder('Default'),
                            ]),
                    ])
                    ->columns(3),

                Section::make('Values & Options')
                    ->schema([
                        TextEntry::make('values')
                            ->label('Available Values')
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return 'No predefined values';
                                }
                                $values = is_array($state) ? $state : json_decode($state, true);
                                if (! is_array($values)) {
                                    return $state;
                                }

                                return implode(', ', $values);
                            })
                            ->badge()
                            ->color('info')
                            ->visible(fn ($state, $record) => ! empty($record->values)),
                    ])
                    ->columns(1)
                    ->visible(fn ($state, $record) => ! empty($record->values)),

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
                                    ->label('Question Age')
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
