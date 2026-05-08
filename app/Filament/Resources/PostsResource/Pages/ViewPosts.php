<?php

namespace App\Filament\Resources\PostsResource\Pages;

use App\Filament\Resources\PostsResource;
use Filament\Actions\Action;
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

class ViewPosts extends ViewRecord
{
    protected static string $resource = PostsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleVisibility')
                ->label(fn ($record) => $record->hidden ? 'Publish' : 'Hide')
                ->icon(fn ($record) => $record->hidden ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                ->color(fn ($record) => $record->hidden ? 'success' : 'warning')
                ->action(function ($record) {
                    $record->update(['hidden' => ! $record->hidden]);
                })
                ->successNotificationTitle(fn ($record) => $record->hidden ? 'Post hidden' : 'Post published'),
            EditAction::make(),
            DeleteAction::make()
                ->after(function () {
                    Cache::forget('posts_count');
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $infolist
            ->record($this->getRecord())
            ->schema([
                Section::make('Post Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Post ID')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('hidden')
                                    ->label('Visibility Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Hidden' : 'Published')
                                    ->color(fn ($state) => $state ? 'danger' : 'success')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-eye-slash' : 'heroicon-o-eye'),

                                TextEntry::make('id')
                                    ->label('Comments Count')
                                    ->state(fn ($record) => $record->postcomments->count())
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-o-chat-bubble-left-right'),
                            ]),

                        TextEntry::make('title')
                            ->label('Title')
                            ->columnSpanFull()
                            ->size(TextSize::Large)
                            ->weight('bold'),

                        ImageEntry::make('image')
                            ->label('Post Image')
                            ->columnSpanFull()
                            ->height(300),
                    ])
                    ->columns(3),

                Section::make('Content')
                    ->schema([
                        TextEntry::make('content')
                            ->label('')
                            ->columnSpanFull()
                            ->prose()
                            ->html(),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('content')
                                    ->label('Character Count')
                                    ->formatStateUsing(fn ($state) => strlen(strip_tags($state)).' characters')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('content')
                                    ->label('Word Count')
                                    ->formatStateUsing(fn ($state) => str_word_count(strip_tags($state)).' words')
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ])
                    ->columns(1),

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
                                    ->label('Post Age')
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

                                        return 'info';
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
