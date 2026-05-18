<?php

namespace App\Filament\Resources\ScoreResource\Pages;

use App\Filament\Resources\ScoreResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Facades\Cache;

class ViewScore extends ViewRecord
{
    protected static string $resource = ScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->after(function () {
                    Cache::forget('scores_count');
                    Cache::forget('scores_average');
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Score Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Score ID')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('score')
                                    ->label('Score')
                                    ->badge()
                                    ->size(TextSize::Large)
                                    ->color(fn ($state, $record): string => match (true) {
                                        $record->score >= 80 => 'success',
                                        $record->score >= 50 => 'warning',
                                        default => 'danger',
                                    })
                                    ->icon(fn ($state, $record): string => match (true) {
                                        $record->score >= 80 => 'heroicon-o-trophy',
                                        $record->score >= 50 => 'heroicon-o-star',
                                        default => 'heroicon-o-x-circle',
                                    }),

                                TextEntry::make('threshold')
                                    ->label('Threshold')
                                    ->badge()
                                    ->color('info')
                                    ->placeholder('Not set'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('score')
                                    ->label('Performance Percentage')
                                    ->formatStateUsing(function ($state, $record) {
                                        if (! $record->threshold || $record->threshold == 0) {
                                            return 'N/A';
                                        }
                                        $percentage = ($record->score / $record->threshold) * 100;

                                        return number_format($percentage, 2).'%';
                                    })
                                    ->badge()
                                    ->size(TextSize::Large)
                                    ->color(function ($state, $record) {
                                        if (! $record->threshold || $record->threshold == 0) {
                                            return 'gray';
                                        }
                                        $percentage = ($record->score / $record->threshold) * 100;
                                        if ($percentage >= 100) {
                                            return 'success';
                                        }
                                        if ($percentage >= 75) {
                                            return 'warning';
                                        }

                                        return 'danger';
                                    })
                                    ->icon('heroicon-o-chart-bar'),

                                TextEntry::make('score')
                                    ->label('Score Level')
                                    ->formatStateUsing(function ($state, $record) {
                                        return match (true) {
                                            $record->score >= 80 => 'High Performance',
                                            $record->score >= 50 => 'Medium Performance',
                                            default => 'Needs Improvement',
                                        };
                                    })
                                    ->badge()
                                    ->color(function ($state, $record) {
                                        return match (true) {
                                            $record->score >= 80 => 'success',
                                            $record->score >= 50 => 'warning',
                                            default => 'danger',
                                        };
                                    }),
                            ]),
                    ])
                    ->columns(3),

                Section::make('Doctor Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('doctor.name')
                                    ->label('Doctor Name')
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

                                TextEntry::make('doctor.email')
                                    ->label('Doctor Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage('Email copied!')
                                    ->copyMessageDuration(1500),

                                TextEntry::make('doctor.specialty')
                                    ->label('Specialty')
                                    ->icon('heroicon-o-academic-cap')
                                    ->placeholder('Not specified'),

                                TextEntry::make('doctor.phone')
                                    ->label('Doctor Phone')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->placeholder('Not provided'),
                            ]),
                    ])
                    ->columns(2)
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
                                    ->label('Score Age')
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
                                    ->color(function ($state, $record) {
                                        $days = $record->created_at->diffInDays(now());
                                        if ($days < 7) {
                                            return 'success';
                                        }
                                        if ($days < 30) {
                                            return 'warning';
                                        }

                                        return 'danger';
                                    })
                                    ->icon('heroicon-o-clock'),
                            ]),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
