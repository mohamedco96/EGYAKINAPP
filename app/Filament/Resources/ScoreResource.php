<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScoreResource\Pages\CreateScore;
use App\Filament\Resources\ScoreResource\Pages\EditScore;
use App\Filament\Resources\ScoreResource\Pages\ListScores;
use App\Filament\Resources\ScoreResource\Pages\ViewScore;
use App\Models\Score;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ScoreResource extends Resource
{
    protected static ?string $model = Score::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Patient Scores';

    protected static string|\UnitEnum|null $navigationGroup = '🏥 Patient Management';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('scores_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $avgScore = Cache::remember('scores_average', 300, function () {
            return static::getModel()::avg('score') ?? 0;
        });

        if ($avgScore >= 80) {
            return 'success';
        }
        if ($avgScore >= 50) {
            return 'warning';
        }

        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Score Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('doctor_id')
                                    ->relationship('doctor', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Doctor Name'),

                                TextInput::make('score')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->label('Score')
                                    ->helperText('Score value between 0 and 100'),

                                TextInput::make('threshold')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->label('Threshold')
                                    ->helperText('Performance threshold'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['doctor']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('doctor.name')
                    ->label('Doctor Name')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(['users.name', 'users.lname'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name.' '.$record->doctor->lname : 'N/A')
                    ->description(fn ($record) => $record->doctor?->specialty),

                TextColumn::make('score')
                    ->label('Score')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->icon(fn ($state): string => match (true) {
                        $state >= 80 => 'heroicon-o-trophy',
                        $state >= 50 => 'heroicon-o-star',
                        default => 'heroicon-o-x-circle',
                    })
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable()
                    ->searchable(),

                TextColumn::make('threshold')
                    ->label('Threshold')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable()
                    ->default('N/A'),

                TextColumn::make('performance')
                    ->label('Performance %')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->formatStateUsing(function ($record) {
                        if (! $record->threshold || $record->threshold == 0) {
                            return 'N/A';
                        }
                        $percentage = ($record->score / $record->threshold) * 100;

                        return number_format($percentage, 2).'%';
                    })
                    ->badge()
                    ->color(function ($record) {
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
                    }),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s')),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s')),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                SelectFilter::make('score_range')
                    ->label('Score Range')
                    ->options([
                        'high' => 'High (>= 80)',
                        'medium' => 'Medium (50-79)',
                        'low' => 'Low (< 50)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'high' => $query->where('score', '>=', 80),
                            'medium' => $query->whereBetween('score', [50, 79]),
                            'low' => $query->where('score', '<', 50),
                            default => $query,
                        };
                    }),

                SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload(),

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
            ->filtersFormColumns(3)
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
                    ->modalHeading('Score Details')
                    ->modalWidth('3xl'),
                EditAction::make(),
                DeleteAction::make()
                    ->after(function () {
                        Cache::forget('scores_count');
                        Cache::forget('scores_average');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('adjustScores')
                        ->label('Adjust Scores')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->form([
                            Select::make('operation')
                                ->label('Operation')
                                ->options([
                                    'add' => 'Add',
                                    'subtract' => 'Subtract',
                                    'multiply' => 'Multiply',
                                    'set' => 'Set to Value',
                                ])
                                ->required()
                                ->native(false),
                            TextInput::make('value')
                                ->label('Value')
                                ->numeric()
                                ->required()
                                ->minValue(0),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $newScore = match ($data['operation']) {
                                    'add' => min(100, $record->score + $data['value']),
                                    'subtract' => max(0, $record->score - $data['value']),
                                    'multiply' => min(100, $record->score * $data['value']),
                                    'set' => min(100, max(0, $data['value'])),
                                };
                                $record->update(['score' => $newScore]);
                            });
                            Cache::forget('scores_average');
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Scores adjusted successfully'),
                    DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('scores_count');
                            Cache::forget('scores_average');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->emptyStateHeading('No scores yet')
            ->emptyStateDescription('Doctor scores will appear here when they are recorded.')
            ->emptyStateIcon('heroicon-o-trophy');
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
            'index' => ListScores::route('/'),
            'create' => CreateScore::route('/create'),
            'view' => ViewScore::route('/{record}'),
            'edit' => EditScore::route('/{record}/edit'),
        ];
    }
}
