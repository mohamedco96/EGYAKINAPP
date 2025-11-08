<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultationResource\Pages;
use App\Modules\Consultations\Models\Consultation;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ConsultationResource extends Resource
{
    protected static ?string $model = Consultation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Consultations';

    protected static ?string $navigationGroup = '💬 AI & Consultations';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('consultations_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $openCount = Cache::remember('consultations_open_count', 300, function () {
            return static::getModel()::where('is_open', true)->count();
        });

        return $openCount > 0 ? 'success' : 'gray';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Consultation Information')
                    ->description('Details about the consultation request')
                    ->schema([
                        Forms\Components\Select::make('doctor_id')
                            ->relationship('doctor', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Requesting Doctor')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name . ' ' . $record->lname . ' (' . $record->specialty . ')')
                            ->helperText('Doctor who is requesting the consultation'),

                        Forms\Components\Select::make('patient_id')
                            ->relationship('patient', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Patient')
                            ->helperText('Patient for whom the consultation is requested'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'in-progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false)
                            ->label('Status'),

                        Forms\Components\Toggle::make('is_open')
                            ->label('Is Open')
                            ->default(true)
                            ->helperText('Whether this consultation is still accepting replies')
                            ->inline(false),

                        Forms\Components\Textarea::make('consult_message')
                            ->required()
                            ->label('Consultation Message')
                            ->rows(6)
                            ->columnSpanFull()
                            ->maxLength(65535)
                            ->helperText('Describe the medical case and your consultation question'),
                    ])->columns(2),
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
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Requesting Doctor')
                    ->searchable(['users.name', 'users.lname'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name . ' ' . $record->doctor->lname : 'N/A')
                    ->description(fn ($record) => $record->doctor?->specialty)
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('patient.name')
                    ->label('Patient')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'in-progress' => 'info',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (?string $state): string => match ($state) {
                        'completed' => 'heroicon-o-check-circle',
                        'in-progress' => 'heroicon-o-arrow-path',
                        'pending' => 'heroicon-o-clock',
                        'cancelled' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\IconColumn::make('is_open')
                    ->label('Open')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-open')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('consult_message')
                    ->label('Message')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }
                        return null;
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('consultationDoctors.count')
                    ->label('Consultants')
                    ->counts('consultationDoctors')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-user-group')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple()
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_open')
                    ->label('Open Status')
                    ->placeholder('All consultations')
                    ->trueLabel('Open only')
                    ->falseLabel('Closed only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_open', true),
                        false: fn (Builder $query) => $query->where('is_open', false),
                    ),

                Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('Requesting Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name . ' ' . $record->lname),

                Tables\Filters\SelectFilter::make('patient_id')
                    ->label('Patient')
                    ->relationship('patient', 'name')
                    ->searchable()
                    ->preload(),

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
                    ->modalHeading('Consultation Details')
                    ->modalWidth('4xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markAsCompleted')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'completed')
                    ->action(function ($record) {
                        $record->update(['status' => 'completed', 'is_open' => false]);
                        Cache::forget('consultations_open_count');
                    })
                    ->successNotificationTitle('Consultation marked as completed'),
                Tables\Actions\Action::make('toggleOpen')
                    ->label(fn ($record) => $record->is_open ? 'Close' : 'Open')
                    ->icon(fn ($record) => $record->is_open ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn ($record) => $record->is_open ? 'danger' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_open' => !$record->is_open]);
                        Cache::forget('consultations_open_count');
                    })
                    ->successNotificationTitle('Consultation status toggled'),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Cache::forget('consultations_count');
                        Cache::forget('consultations_open_count');
                    }),
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
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each->update(['status' => $data['status']]);
                            if ($data['status'] === 'completed') {
                                $records->each->update(['is_open' => false]);
                            }
                            Cache::forget('consultations_open_count');
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Status updated for selected consultations'),
                    Tables\Actions\BulkAction::make('markAsCompleted')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each->update(['status' => 'completed', 'is_open' => false]);
                            Cache::forget('consultations_open_count');
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected consultations marked as completed'),
                    Tables\Actions\BulkAction::make('closeConsultations')
                        ->label('Close Consultations')
                        ->icon('heroicon-o-lock-closed')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each->update(['is_open' => false]);
                            Cache::forget('consultations_open_count');
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected consultations closed'),
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('consultations_count');
                            Cache::forget('consultations_open_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->emptyStateHeading('No consultations yet')
            ->emptyStateDescription('Doctor consultation requests will appear here.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
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
            'index' => Pages\ListConsultations::route('/'),
            'create' => Pages\CreateConsultation::route('/create'),
            'view' => Pages\ViewConsultation::route('/{record}'),
            'edit' => Pages\EditConsultation::route('/{record}/edit'),
        ];
    }
}
