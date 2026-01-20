<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Cache;

class ViewContact extends ViewRecord
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('markAsResolved')
                ->label('Mark as Resolved')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($state, $record) => $record->status !== 'resolved')
                ->action(function ($state, $record) {
                    $record->update(['status' => 'resolved']);
                    Cache::forget('contacts_pending_count');
                })
                ->successNotificationTitle('Contact marked as resolved'),
            Actions\Action::make('markAsInProgress')
                ->label('Mark as In Progress')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn ($state, $record) => $record->status === 'pending')
                ->action(function ($state, $record) {
                    $record->update(['status' => 'in-progress']);
                    Cache::forget('contacts_pending_count');
                })
                ->successNotificationTitle('Contact marked as in progress'),
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    Cache::forget('contacts_count');
                    Cache::forget('contacts_pending_count');
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->getRecord())
            ->schema([
                Infolists\Components\Section::make('Contact Request Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('Contact ID')
                                    ->badge()
                                    ->color('gray'),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'resolved' => 'success',
                                        'in-progress' => 'info',
                                        'pending' => 'warning',
                                        default => 'gray',
                                    })
                                    ->icon(fn (string $state): string => match ($state) {
                                        'resolved' => 'heroicon-o-check-circle',
                                        'in-progress' => 'heroicon-o-arrow-path',
                                        'pending' => 'heroicon-o-clock',
                                        default => 'heroicon-o-question-mark-circle',
                                    }),

                                Infolists\Components\TextEntry::make('priority')
                                    ->label('Priority')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'high' => 'danger',
                                        'medium' => 'warning',
                                        'low' => 'success',
                                        default => 'gray',
                                    })
                                    ->icon(fn (string $state): string => match ($state) {
                                        'high' => 'heroicon-o-exclamation-triangle',
                                        'medium' => 'heroicon-o-exclamation-circle',
                                        'low' => 'heroicon-o-information-circle',
                                        default => 'heroicon-o-minus-circle',
                                    }),
                            ]),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Doctor Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('doctor.name')
                                    ->label('Doctor Name')
                                    ->formatStateUsing(fn ($state, $record) =>
                                        $record->doctor
                                            ? $record->doctor->name . ' ' . $record->doctor->lname
                                            : 'N/A'
                                    )
                                    ->icon('heroicon-o-user-circle')
                                    ->url(fn ($state, $record) =>
                                        $record->doctor_id
                                            ? route('filament.admin.resources.users.edit', ['record' => $record->doctor_id])
                                            : null
                                    )
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('doctor.email')
                                    ->label('Doctor Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage('Email copied!')
                                    ->copyMessageDuration(1500),

                                Infolists\Components\TextEntry::make('doctor.phone')
                                    ->label('Doctor Phone')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->placeholder('Not provided'),

                                Infolists\Components\TextEntry::make('doctor.specialty')
                                    ->label('Specialty')
                                    ->icon('heroicon-o-academic-cap')
                                    ->placeholder('Not specified'),
                            ]),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make('Message')
                    ->schema([
                        Infolists\Components\TextEntry::make('message')
                            ->label('')
                            ->columnSpanFull()
                            ->prose()
                            ->markdown(),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('message')
                                    ->label('Character Count')
                                    ->formatStateUsing(fn ($state) => strlen($state) . ' characters')
                                    ->badge()
                                    ->color(fn ($state) => strlen($state) > 500 ? 'warning' : 'success'),

                                Infolists\Components\TextEntry::make('message')
                                    ->label('Word Count')
                                    ->formatStateUsing(fn ($state) => str_word_count($state) . ' words')
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Submitted')
                                    ->since()
                                    ->icon('heroicon-o-clock'),
                            ]),
                    ])
                    ->columns(1),

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
                                    ->label('Response Time')
                                    ->formatStateUsing(function ($state, $record) {
                                        if ($record->status === 'pending') {
                                            return 'Pending';
                                        }
                                        if ($record->updated_at && $record->created_at) {
                                            $diff = $record->created_at->diff($record->updated_at);
                                            if ($diff->days > 0) {
                                                return $diff->days . ' days';
                                            }
                                            if ($diff->h > 0) {
                                                return $diff->h . ' hours';
                                            }
                                            return $diff->i . ' minutes';
                                        }
                                        return 'N/A';
                                    })
                                    ->badge()
                                    ->color(function ($state, $record) {
                                        if ($record->status === 'pending') return 'warning';
                                        if ($record->updated_at && $record->created_at) {
                                            $hours = $record->created_at->diffInHours($record->updated_at);
                                            if ($hours < 24) return 'success';
                                            if ($hours < 72) return 'warning';
                                            return 'danger';
                                        }
                                        return 'gray';
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
