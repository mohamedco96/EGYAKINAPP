<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Modules\Notifications\Models\AppNotification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class NotificationResource extends Resource
{
    protected static ?string $model = AppNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $navigationGroup = '📢 Communications';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('notifications_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $unreadCount = Cache::remember('notifications_unread_count', 300, function () {
            return static::getModel()::where('read', false)->count();
        });

        return $unreadCount > 0 ? 'warning' : 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Consultation' => 'info',
                        'Recommendation' => 'success',
                        'Patient' => 'warning',
                        'Score' => 'primary',
                        'System' => 'gray',
                        default => 'secondary',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Consultation' => 'heroicon-o-chat-bubble-left-right',
                        'Recommendation' => 'heroicon-o-light-bulb',
                        'Patient' => 'heroicon-o-user',
                        'Score' => 'heroicon-o-chart-bar',
                        'System' => 'heroicon-o-cog',
                        default => 'heroicon-o-bell',
                    })
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor Name')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(['users.name', 'users.lname'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name . ' ' . $record->doctor->lname : 'N/A')
                    ->description(fn ($record) => $record->doctor?->email),

                Tables\Columns\TextColumn::make('patient_id')
                    ->label('Patient ID')
                    ->badge()
                    ->prefix('#')
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('content')
                    ->label('Content')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable()
                    ->limit(80)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 80) {
                            return $state;
                        }
                        return null;
                    })
                    ->wrap(),

                Tables\Columns\IconColumn::make('read')
                    ->label('Read Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('read')
                    ->label('Mark Read')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Notification Type')
                    ->options([
                        'Consultation' => 'Consultation',
                        'Recommendation' => 'Recommendation',
                        'Patient' => 'Patient',
                        'Score' => 'Score',
                        'System' => 'System',
                    ])
                    ->multiple()
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('read')
                    ->label('Read Status')
                    ->placeholder('All notifications')
                    ->trueLabel('Read only')
                    ->falseLabel('Unread only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('read', true),
                        false: fn (Builder $query) => $query->where('read', false),
                    ),

                Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('patient_id')
                    ->label('Patient ID')
                    ->searchable()
                    ->options(function () {
                        return \App\Modules\Patients\Models\Patients::query()
                            ->limit(100)
                            ->pluck('id', 'id')
                            ->toArray();
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('From'),
                        DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'From ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(5)
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Toggle columns'),
            )
            ->persistFiltersInSession()
            ->deselectAllRecordsWhenFiltered(true)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Notification Details')
                    ->modalWidth('2xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markAsRead')
                    ->label('Mark Read')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => !$record->read)
                    ->action(function ($record) {
                        $record->update(['read' => true]);
                        Cache::forget('notifications_unread_count');
                    })
                    ->successNotificationTitle('Notification marked as read'),
                Tables\Actions\Action::make('markAsUnread')
                    ->label('Mark Unread')
                    ->icon('heroicon-o-x-mark')
                    ->color('warning')
                    ->visible(fn ($record) => $record->read)
                    ->action(function ($record) {
                        $record->update(['read' => false]);
                        Cache::forget('notifications_unread_count');
                    })
                    ->successNotificationTitle('Notification marked as unread'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markAsRead')
                        ->label('Mark as Read')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each->update(['read' => true]);
                            Cache::forget('notifications_unread_count');
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected notifications marked as read'),
                    Tables\Actions\BulkAction::make('markAsUnread')
                        ->label('Mark as Unread')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each->update(['read' => false]);
                            Cache::forget('notifications_unread_count');
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected notifications marked as unread'),
                    Tables\Actions\BulkAction::make('deleteByType')
                        ->label('Delete by Type')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            \Filament\Forms\Components\Select::make('type')
                                ->label('Select Type to Delete')
                                ->options([
                                    'Consultation' => 'Consultation',
                                    'Recommendation' => 'Recommendation',
                                    'Patient' => 'Patient',
                                    'Score' => 'Score',
                                    'System' => 'System',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->where('type', $data['type'])->each->delete();
                            Cache::forget('notifications_count');
                            Cache::forget('notifications_unread_count');
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Notifications deleted'),
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('notifications_count');
                            Cache::forget('notifications_unread_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->emptyStateHeading('No notifications yet')
            ->emptyStateDescription('Notifications will appear here when doctors and patients interact with the system.')
            ->emptyStateIcon('heroicon-o-bell-slash');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'view' => Pages\ViewNotification::route('/{record}'),
            'edit' => Pages\EditNotification::route('/{record}/edit'),
        ];
    }
}
