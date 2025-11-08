<?php

namespace App\Filament\Resources\QuestionsResource\Pages;

use App\Filament\Resources\QuestionsResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Cache;

class ViewQuestions extends ViewRecord
{
    protected static string $resource = QuestionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    Cache::forget('questions_count');
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Question Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('Question ID')
                                    ->badge()
                                    ->color('gray'),

                                Infolists\Components\TextEntry::make('type')
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

                                Infolists\Components\TextEntry::make('sort')
                                    ->label('Sort Order')
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-arrows-up-down'),
                            ]),

                        Infolists\Components\TextEntry::make('question')
                            ->label('Question Text')
                            ->columnSpanFull()
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Section Details')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('section_id')
                                    ->label('Section ID')
                                    ->badge()
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('section_name')
                                    ->label('Section Name')
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-folder'),
                            ]),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Configuration')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('mandatory')
                                    ->label('Mandatory')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Required' : 'Optional')
                                    ->color(fn ($state) => $state ? 'success' : 'gray')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),

                                Infolists\Components\TextEntry::make('hidden')
                                    ->label('Visibility')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Hidden' : 'Visible')
                                    ->color(fn ($state) => $state ? 'warning' : 'success')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-eye-slash' : 'heroicon-o-eye'),

                                Infolists\Components\TextEntry::make('keyboard_type')
                                    ->label('Keyboard Type')
                                    ->badge()
                                    ->color('secondary')
                                    ->icon('heroicon-o-keyboard')
                                    ->placeholder('Default'),
                            ]),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Values & Options')
                    ->schema([
                        Infolists\Components\TextEntry::make('values')
                            ->label('Available Values')
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) return 'No predefined values';
                                $values = is_array($state) ? $state : json_decode($state, true);
                                if (!is_array($values)) return $state;
                                return implode(', ', $values);
                            })
                            ->badge()
                            ->color('info')
                            ->visible(fn ($state, $record) => !empty($record->values)),
                    ])
                    ->columns(1)
                    ->visible(fn ($state, $record) => !empty($record->values)),

                Infolists\Components\Section::make('Timeline')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-calendar')
                                    ->tooltip(fn ($state, $record) => $record->created_at?->format('M d, Y H:i:s')),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-pencil')
                                    ->tooltip(fn ($state, $record) => $record->updated_at?->format('M d, Y H:i:s')),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Question Age')
                                    ->formatStateUsing(function ($state, $record) {
                                        $diff = $record->created_at->diff(now());
                                        if ($diff->days > 0) {
                                            return $diff->days . ' days old';
                                        }
                                        if ($diff->h > 0) {
                                            return $diff->h . ' hours old';
                                        }
                                        return $diff->i . ' minutes old';
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
