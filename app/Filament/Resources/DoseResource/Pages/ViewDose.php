<?php

namespace App\Filament\Resources\DoseResource\Pages;

use App\Filament\Resources\DoseResource;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewDose extends ViewRecord
{
    protected static string $resource = DoseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->color('warning')
                ->icon('heroicon-m-pencil-square'),
            Actions\DeleteAction::make()
                ->color('danger')
                ->icon('heroicon-m-trash')
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to delete this dose modifier? This action cannot be undone.'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Dose Modifier Details')
                    ->description('Complete information about this dose modifier')
                    ->icon('heroicon-m-beaker')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Title')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg')
                                    ->color('primary')
                                    ->icon('heroicon-m-beaker')
                                    ->copyable()
                                    ->copyMessage('Title copied!')
                                    ->columnSpan(2),
                            ]),

                        Grid::make(1)
                            ->schema([
                                TextEntry::make('description')
                                    ->label('Description')
                                    ->html()
                                    ->placeholder('No description provided')
                                    ->columnSpanFull(),
                            ]),

                        Grid::make(1)
                            ->schema([
                                TextEntry::make('dose')
                                    ->label('Dosage Information')
                                    ->html()
                                    ->weight(FontWeight::Medium)
                                    ->color('success')
                                    ->icon('heroicon-m-calculator')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Section::make('Metadata')
                    ->description('Creation and modification information')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('F j, Y \a\t g:i A')
                                    ->icon('heroicon-m-calendar-days')
                                    ->color('gray'),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('F j, Y \a\t g:i A')
                                    ->icon('heroicon-m-clock')
                                    ->color('gray'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('ID')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('created_at')
                                    ->label('Age')
                                    ->since()
                                    ->icon('heroicon-m-clock')
                                    ->color('primary'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->persistCollapsed(),
            ]);
    }

    public function getTitle(): string
    {
        return 'View Dose Modifier';
    }

    public function getSubheading(): ?string
    {
        return 'Detailed view of medication dosing guideline';
    }
}
