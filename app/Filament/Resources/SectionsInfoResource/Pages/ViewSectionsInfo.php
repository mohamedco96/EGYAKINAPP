<?php

namespace App\Filament\Resources\SectionsInfoResource\Pages;

use App\Filament\Resources\SectionsInfoResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Cache;

class ViewSectionsInfo extends ViewRecord
{
    protected static string $resource = SectionsInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    Cache::forget('sections_info_count');
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->getRecord())
            ->schema([
                Infolists\Components\Section::make('Section Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('Section ID')
                                    ->badge()
                                    ->color('gray'),

                                Infolists\Components\TextEntry::make('questions')
                                    ->label('Total Questions')
                                    ->formatStateUsing(fn ($state, $record) => $record->questions->count())
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-o-question-mark-circle'),
                            ]),

                        Infolists\Components\TextEntry::make('section_name')
                            ->label('Section Name')
                            ->columnSpanFull()
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->icon('heroicon-o-folder'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Description')
                    ->schema([
                        Infolists\Components\TextEntry::make('section_description')
                            ->label('')
                            ->columnSpanFull()
                            ->prose()
                            ->markdown()
                            ->placeholder('No description provided'),
                    ])
                    ->columns(1)
                    ->visible(fn ($state, $record) => !empty($record->section_description)),

                Infolists\Components\Section::make('Questions Breakdown')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('mandatory_questions')
                                    ->label('Mandatory')
                                    ->formatStateUsing(fn ($state, $record) => $record->questions->where('mandatory', true)->count())
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-o-check-circle'),

                                Infolists\Components\TextEntry::make('optional_questions')
                                    ->label('Optional')
                                    ->formatStateUsing(fn ($state, $record) => $record->questions->where('mandatory', false)->count())
                                    ->badge()
                                    ->color('gray')
                                    ->icon('heroicon-o-minus-circle'),

                                Infolists\Components\TextEntry::make('hidden_questions')
                                    ->label('Hidden')
                                    ->formatStateUsing(fn ($state, $record) => $record->questions->where('hidden', true)->count())
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-eye-slash'),

                                Infolists\Components\TextEntry::make('visible_questions')
                                    ->label('Visible')
                                    ->formatStateUsing(fn ($state, $record) => $record->questions->where('hidden', false)->count())
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-o-eye'),
                            ]),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->visible(fn ($state, $record) => $record->questions->count() > 0),

                Infolists\Components\Section::make('Question Types')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('string_questions')
                                    ->label('String')
                                    ->formatStateUsing(fn ($state, $record) => $record->questions->where('type', 'string')->count())
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-o-pencil'),

                                Infolists\Components\TextEntry::make('select_questions')
                                    ->label('Select')
                                    ->formatStateUsing(fn ($state, $record) => $record->questions->where('type', 'select')->count())
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-chevron-down'),

                                Infolists\Components\TextEntry::make('multiple_questions')
                                    ->label('Multiple')
                                    ->formatStateUsing(fn ($state, $record) => $record->questions->where('type', 'multiple')->count())
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-queue-list'),

                                Infolists\Components\TextEntry::make('date_questions')
                                    ->label('Date')
                                    ->formatStateUsing(fn ($state, $record) => $record->questions->where('type', 'date')->count())
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-o-calendar'),
                            ]),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->visible(fn ($state, $record) => $record->questions->count() > 0),

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
                                    ->label('Section Age')
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
