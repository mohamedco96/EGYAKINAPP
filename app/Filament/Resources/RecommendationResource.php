<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecommendationResource\Pages\CreateRecommendation;
use App\Filament\Resources\RecommendationResource\Pages\EditRecommendation;
use App\Filament\Resources\RecommendationResource\Pages\ListRecommendations;
use App\Filament\Resources\RecommendationResource\Pages\ViewRecommendation;
use App\Modules\Patients\Models\Patients;
use App\Modules\Recommendations\Models\Recommendation;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class RecommendationResource extends Resource
{
    protected static ?string $model = Recommendation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Recommendations';

    protected static string|\UnitEnum|null $navigationGroup = '📊 Medical Data';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('recommendations_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Recommendation Information')
                    ->description('Medical recommendation details')
                    ->schema([
                        Select::make('patient_id')
                            ->relationship('patient', 'id')
                            ->getSearchResultsUsing(function (string $search) {
                                return Patients::query()
                                    ->with('doctor')
                                    ->where('id', 'like', "%{$search}%")
                                    ->orWhereHas('doctor', function ($query) use ($search) {
                                        $query->where('name', 'like', "%{$search}%")
                                            ->orWhere('lname', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($patient) {
                                        $doctorName = $patient->doctor ? $patient->doctor->name.' '.($patient->doctor->lname ?? '') : 'Unknown';

                                        return [$patient->id => "Patient #{$patient->id} (Doctor: {$doctorName})"];
                                    });
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $patient = Patients::with('doctor')->find($value);
                                if (! $patient) {
                                    return "Patient #{$value}";
                                }
                                $doctorName = $patient->doctor ? $patient->doctor->name.' '.($patient->doctor->lname ?? '') : 'Unknown';

                                return "Patient #{$patient->id} (Doctor: {$doctorName})";
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Patient')
                            ->helperText('Select the patient for this recommendation'),

                        Select::make('type')
                            ->options([
                                'medication' => 'Medication',
                                'procedure' => 'Procedure',
                                'lifestyle' => 'Lifestyle',
                                'follow-up' => 'Follow-up',
                                'dietary' => 'Dietary',
                                'other' => 'Other',
                                'note' => 'Note (Legacy)',
                                'rec' => 'Recommendation (Legacy)',
                            ])
                            ->required()
                            ->native(false)
                            ->label('Recommendation Type')
                            ->reactive(),

                        Textarea::make('content')
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
                        TextInput::make('dose_name')
                            ->label('Medication Name')
                            ->maxLength(255)
                            ->helperText('Name of the medication'),

                        TextInput::make('dose')
                            ->label('Dosage')
                            ->maxLength(255)
                            ->helperText('e.g., 500mg, 10ml'),

                        TextInput::make('route')
                            ->label('Route of Administration')
                            ->maxLength(255)
                            ->helperText('e.g., Oral, IV, IM'),

                        TextInput::make('frequency')
                            ->label('Frequency')
                            ->maxLength(255)
                            ->helperText('e.g., Twice daily, Every 8 hours'),

                        TextInput::make('duration')
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
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('patient_id')
                    ->label('Patient')
                    ->formatStateUsing(function ($record) {
                        if (! $record->patient) {
                            return "Patient #{$record->patient_id}";
                        }
                        $doctorName = $record->patient->doctor ? $record->patient->doctor->name.' '.($record->patient->doctor->lname ?? '') : 'Unknown';

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

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'medication' => 'success',
                        'procedure' => 'info',
                        'lifestyle' => 'warning',
                        'follow-up' => 'primary',
                        'dietary' => 'secondary',
                        'other' => 'gray',
                        'note' => 'gray',
                        'rec' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'medication' => 'heroicon-o-beaker',
                        'procedure' => 'heroicon-o-wrench-screwdriver',
                        'lifestyle' => 'heroicon-o-heart',
                        'follow-up' => 'heroicon-o-calendar',
                        'dietary' => 'heroicon-o-shopping-bag',
                        'other' => 'heroicon-o-document',
                        'note' => 'heroicon-o-pencil-square',
                        'rec' => 'heroicon-o-clipboard-document-list',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('content')
                    ->label('Content')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }

                        return null;
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('dose_name')
                    ->label('Medication')
                    ->searchable()
                    ->placeholder('N/A')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('dose')
                    ->label('Dosage')
                    ->searchable()
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('route')
                    ->label('Route')
                    ->searchable()
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('frequency')
                    ->label('Frequency')
                    ->searchable()
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('duration')
                    ->label('Duration')
                    ->searchable()
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                SelectFilter::make('type')
                    ->label('Recommendation Type')
                    ->options([
                        'medication' => 'Medication',
                        'procedure' => 'Procedure',
                        'lifestyle' => 'Lifestyle',
                        'follow-up' => 'Follow-up',
                        'dietary' => 'Dietary',
                        'other' => 'Other',
                        'note' => 'Note (Legacy)',
                        'rec' => 'Recommendation (Legacy)',
                    ])
                    ->multiple()
                    ->searchable(),

                SelectFilter::make('patient_id')
                    ->label('Patient')
                    ->relationship('patient', 'id')
                    ->getOptionLabelUsing(function ($value) {
                        $patient = Patients::with('doctor')->find($value);
                        if (! $patient) {
                            return "Patient #{$value}";
                        }
                        $doctorName = $patient->doctor ? $patient->doctor->name.' '.($patient->doctor->lname ?? '') : 'Unknown';

                        return "Patient #{$patient->id} (Doctor: {$doctorName})";
                    })
                    ->searchable()
                    ->preload(),

                Filter::make('has_medication')
                    ->label('Has Medication Details')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('dose_name')),

                Filter::make('created_at')
                    ->schema([
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
                            $indicators[] = 'From '.Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until '.Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Toggle columns'),
            )
            ->persistFiltersInSession()
            ->deferFilters(false)
            ->deselectAllRecordsWhenFiltered(true)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Recommendation Details')
                    ->modalWidth('4xl'),
                EditAction::make(),
                DeleteAction::make()
                    ->after(function () {
                        Cache::forget('recommendations_count');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('recommendations_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
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
            'index' => ListRecommendations::route('/'),
            'create' => CreateRecommendation::route('/create'),
            'view' => ViewRecommendation::route('/{record}'),
            'edit' => EditRecommendation::route('/{record}/edit'),
        ];
    }
}
