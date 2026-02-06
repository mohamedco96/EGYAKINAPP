<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecommendationResource\Pages;
use App\Modules\Recommendations\Models\Recommendation;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class RecommendationResource extends Resource
{
    protected static ?string $model = Recommendation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Recommendations';

    protected static ?string $navigationGroup = '📊 Medical Data';

    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('recommendations_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Recommendation Information')
                    ->description('Medical recommendation details')
                    ->schema([
                        Forms\Components\Select::make('patient_id')
                            ->relationship('patient', 'id')
                            ->getSearchResultsUsing(function (string $search) {
                                return \App\Models\Patient::query()
                                    ->where('id', 'like', "%{$search}%")
                                    ->orWhereHas('doctor', function ($query) use ($search) {
                                        $query->where('name', 'like', "%{$search}%")
                                            ->orWhere('lname', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($patient) {
                                        $doctorName = $patient->doctor ? $patient->doctor->name . ' ' . ($patient->doctor->lname ?? '') : 'Unknown';
                                        return [$patient->id => "Patient #{$patient->id} (Doctor: {$doctorName})"];
                                    });
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $patient = \App\Models\Patient::find($value);
                                if (!$patient) return "Patient #{$value}";
                                $doctorName = $patient->doctor ? $patient->doctor->name . ' ' . ($patient->doctor->lname ?? '') : 'Unknown';
                                return "Patient #{$patient->id} (Doctor: {$doctorName})";
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Patient')
                            ->helperText('Select the patient for this recommendation'),

                        Forms\Components\Select::make('type')
                            ->options([
                                'medication' => 'Medication',
                                'procedure' => 'Procedure',
                                'lifestyle' => 'Lifestyle',
                                'follow-up' => 'Follow-up',
                                'dietary' => 'Dietary',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->native(false)
                            ->label('Recommendation Type')
                            ->reactive(),

                        Forms\Components\Textarea::make('content')
                            ->required()
                            ->label('Recommendation Content')
                            ->rows(4)
                            ->columnSpanFull()
                            ->maxLength(65535)
                            ->helperText('Describe the medical recommendation'),
                    ])->columns(2),

                Section::make('Medication Details')
                    ->description('Specific medication information (if applicable)')
                    ->schema([
                        Forms\Components\TextInput::make('dose_name')
                            ->label('Medication Name')
                            ->maxLength(255)
                            ->helperText('Name of the medication'),

                        Forms\Components\TextInput::make('dose')
                            ->label('Dosage')
                            ->maxLength(255)
                            ->helperText('e.g., 500mg, 10ml'),

                        Forms\Components\TextInput::make('route')
                            ->label('Route of Administration')
                            ->maxLength(255)
                            ->helperText('e.g., Oral, IV, IM'),

                        Forms\Components\TextInput::make('frequency')
                            ->label('Frequency')
                            ->maxLength(255)
                            ->helperText('e.g., Twice daily, Every 8 hours'),

                        Forms\Components\TextInput::make('duration')
                            ->label('Duration')
                            ->maxLength(255)
                            ->helperText('e.g., 7 days, 2 weeks'),
                    ])->columns(2)->collapsible()->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['patient.doctor']))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('patient_id')
                    ->label('Patient')
                    ->formatStateUsing(function ($record) {
                        if (!$record->patient) return "Patient #{$record->patient_id}";
                        $doctorName = $record->patient->doctor ? $record->patient->doctor->name . ' ' . ($record->patient->doctor->lname ?? '') : 'Unknown';
                        return "Patient #{$record->patient_id} (Doctor: {$doctorName})";
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->where('patient_id', 'like', "%{$search}%")
                            ->orWhereHas('patient.doctor', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                    ->orWhere('lname', 'like', "%{$search}%");
                            });
                    })
                    ->sortable()
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'medication' => 'success',
                        'procedure' => 'info',
                        'lifestyle' => 'warning',
                        'follow-up' => 'primary',
                        'dietary' => 'secondary',
                        'other' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'medication' => 'heroicon-o-beaker',
                        'procedure' => 'heroicon-o-wrench-screwdriver',
                        'lifestyle' => 'heroicon-o-heart',
                        'follow-up' => 'heroicon-o-calendar',
                        'dietary' => 'heroicon-o-shopping-bag',
                        'other' => 'heroicon-o-document',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('content')
                    ->label('Content')
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

                Tables\Columns\TextColumn::make('dose_name')
                    ->label('Medication')
                    ->searchable()
                    ->placeholder('N/A')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('dose')
                    ->label('Dosage')
                    ->searchable()
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('route')
                    ->label('Route')
                    ->searchable()
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('frequency')
                    ->label('Frequency')
                    ->searchable()
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->searchable()
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                Tables\Filters\SelectFilter::make('type')
                    ->label('Recommendation Type')
                    ->options([
                        'medication' => 'Medication',
                        'procedure' => 'Procedure',
                        'lifestyle' => 'Lifestyle',
                        'follow-up' => 'Follow-up',
                        'dietary' => 'Dietary',
                        'other' => 'Other',
                    ])
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('patient_id')
                    ->label('Patient')
                    ->relationship('patient', 'id')
                    ->getOptionLabelUsing(function ($value) {
                        $patient = \App\Models\Patient::find($value);
                        if (!$patient) return "Patient #{$value}";
                        $doctorName = $patient->doctor ? $patient->doctor->name . ' ' . ($patient->doctor->lname ?? '') : 'Unknown';
                        return "Patient #{$patient->id} (Doctor: {$doctorName})";
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('has_medication')
                    ->label('Has Medication Details')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('dose_name')),

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
            ->filtersFormColumns(4)
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
                    ->modalHeading('Recommendation Details')
                    ->modalWidth('4xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Cache::forget('recommendations_count');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('recommendations_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->emptyStateHeading('No recommendations yet')
            ->emptyStateDescription('Medical recommendations will appear here.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
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
            'index' => Pages\ListRecommendations::route('/'),
            'create' => Pages\CreateRecommendation::route('/create'),
            'view' => Pages\ViewRecommendation::route('/{record}'),
            'edit' => Pages\EditRecommendation::route('/{record}/edit'),
        ];
    }
}
