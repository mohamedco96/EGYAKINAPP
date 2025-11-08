<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Modules\Contacts\Models\Contact;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'Contact Requests';

    protected static ?string $navigationGroup = '📢 Communications';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('contacts_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = Cache::remember('contacts_pending_count', 300, function () {
            return static::getModel()::where('status', 'pending')->count();
        });

        return $pendingCount > 0 ? 'warning' : 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('doctor_id')
                                    ->relationship('doctor', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Doctor Name'),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'in-progress' => 'In Progress',
                                        'resolved' => 'Resolved',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->label('Status')
                                    ->native(false),

                                Forms\Components\Select::make('priority')
                                    ->options([
                                        'low' => 'Low',
                                        'medium' => 'Medium',
                                        'high' => 'High',
                                    ])
                                    ->default('medium')
                                    ->required()
                                    ->label('Priority')
                                    ->native(false),
                            ]),

                        Forms\Components\Textarea::make('message')
                            ->required()
                            ->label('Message')
                            ->rows(5)
                            ->columnSpanFull()
                            ->maxLength(65535),
                    ]),
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

                Tables\Columns\TextColumn::make('priority')
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
                    })
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
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
                    })
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor Name')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(['users.name', 'users.lname'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name . ' ' . $record->doctor->lname : 'N/A')
                    ->description(fn ($record) => $record->doctor?->email),

                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }
                        return null;
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('message')
                    ->label('Message Length')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => strlen($state) . ' chars')
                    ->badge()
                    ->color(fn ($state) => strlen($state) > 500 ? 'warning' : 'success'),

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
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s')),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'in-progress' => 'In Progress',
                        'resolved' => 'Resolved',
                    ])
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ])
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('message_length')
                    ->label('Message Length')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('min_length')
                                    ->label('Min Characters')
                                    ->numeric()
                                    ->minValue(0),
                                Forms\Components\TextInput::make('max_length')
                                    ->label('Max Characters')
                                    ->numeric()
                                    ->minValue(0),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_length'],
                                fn (Builder $query, $length): Builder =>
                                    $query->whereRaw('LENGTH(message) >= ?', [$length])
                            )
                            ->when(
                                $data['max_length'],
                                fn (Builder $query, $length): Builder =>
                                    $query->whereRaw('LENGTH(message) <= ?', [$length])
                            );
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
                    ->modalHeading('Contact Request Details')
                    ->modalWidth('3xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markAsResolved')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'resolved')
                    ->action(function ($record) {
                        $record->update(['status' => 'resolved']);
                        Cache::forget('contacts_pending_count');
                    })
                    ->successNotificationTitle('Contact marked as resolved'),
                Tables\Actions\Action::make('markAsInProgress')
                    ->label('In Progress')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update(['status' => 'in-progress']);
                        Cache::forget('contacts_pending_count');
                    })
                    ->successNotificationTitle('Contact marked as in progress'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('changeStatus')
                        ->label('Change Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'in-progress' => 'In Progress',
                                    'resolved' => 'Resolved',
                                ])
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each->update(['status' => $data['status']]);
                            Cache::forget('contacts_pending_count');
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Status updated for selected contacts'),
                    Tables\Actions\BulkAction::make('changePriority')
                        ->label('Change Priority')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('priority')
                                ->label('New Priority')
                                ->options([
                                    'low' => 'Low',
                                    'medium' => 'Medium',
                                    'high' => 'High',
                                ])
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each->update(['priority' => $data['priority']]);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Priority updated for selected contacts'),
                    Tables\Actions\BulkAction::make('markAsResolved')
                        ->label('Mark as Resolved')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each->update(['status' => 'resolved']);
                            Cache::forget('contacts_pending_count');
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected contacts marked as resolved'),
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('contacts_count');
                            Cache::forget('contacts_pending_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->emptyStateHeading('No contact requests yet')
            ->emptyStateDescription('Contact requests from doctors will appear here.')
            ->emptyStateIcon('heroicon-o-phone-x-mark');
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'view' => Pages\ViewContact::route('/{record}'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
