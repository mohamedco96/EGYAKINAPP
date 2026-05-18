<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionsResource\Pages\CreateQuestions;
use App\Filament\Resources\QuestionsResource\Pages\EditQuestions;
use App\Filament\Resources\QuestionsResource\Pages\ListQuestions;
use App\Filament\Resources\QuestionsResource\Pages\ViewQuestions;
use App\Models\SectionsInfo;
use App\Modules\Questions\Models\Questions;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class QuestionsResource extends Resource
{
    protected static ?string $model = Questions::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'Questions';

    protected static string|\UnitEnum|null $navigationGroup = '📊 Medical Data';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('questions_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('section_id')
                    ->required(), // Make sure it's required

                Select::make('section_name')
                    ->label('Section Name')
                    ->options(function () {
                        return SectionsInfo::pluck('section_name', 'section_name')->toArray();
                    })
                    ->reactive()
                    ->required()
                    ->helperText('Select the section by name')
                    ->afterStateUpdated(function ($set, $get, $state) {
                        // Set section_id based on the selected section_name
                        $sectionId = SectionsInfo::where('section_name', $state)->value('id');
                        if ($sectionId) {
                            $set('section_id', $sectionId);
                        }
                    }),

                TextInput::make('question')
                    ->label('Question')
                    ->required(),

                TextInput::make('sort')
                    ->label('Sort Order')
                    ->numeric(),

                Select::make('type')
                    ->label('Type')
                    ->options([
                        'string' => 'String',
                        'select' => 'Select',
                        'multiple' => 'Multiple Select',
                        'date' => 'Date',
                    ])
                    ->reactive()
                    ->required(),

                TagsInput::make('values')
                    ->label('Values')
                    ->placeholder('Enter question options')
                    ->visible(fn ($get) => in_array($get('type'), ['select', 'multiple']))
                    ->visibleJs('$get(\'type\') === \'select\' || $get(\'type\') === \'multiple\'')
                    ->required(),

                Select::make('keyboard_type')
                    ->label('Keyboard Type')
                    ->options([
                        'text' => 'Text',
                        'number' => 'Number',
                        'email' => 'Email',
                    ])
                    ->default('text'),

                Radio::make('mandatory')
                    ->label('Mandatory')
                    ->required()
                    ->boolean()
                    ->default(true),

                Radio::make('hidden')
                    ->label('Hidden')
                    ->boolean()
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('section_name')
                    ->label('Section')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('question')
                    ->label('Question')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->limit(80)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 80) {
                            return $state;
                        }

                        return null;
                    })
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'string' => 'success',
                        'select' => 'warning',
                        'multiple' => 'info',
                        'date' => 'primary',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'string' => 'heroicon-o-pencil',
                        'select' => 'heroicon-o-chevron-down',
                        'multiple' => 'heroicon-o-queue-list',
                        'date' => 'heroicon-o-calendar',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('values')
                    ->label('Values')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return 'N/A';
                        }
                        $values = is_array($state) ? $state : json_decode($state, true);
                        if (! is_array($values)) {
                            return $state;
                        }

                        return implode(', ', array_slice($values, 0, 3)).(count($values) > 3 ? '...' : '');
                    })
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (empty($state)) {
                            return null;
                        }
                        $values = is_array($state) ? $state : json_decode($state, true);
                        if (! is_array($values)) {
                            return null;
                        }

                        return implode(', ', $values);
                    }),

                TextInputColumn::make('sort')
                    ->label('Sort Order')
                    ->rules(['required', 'numeric'])
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('keyboard_type')
                    ->label('Keyboard')
                    ->badge()
                    ->color('secondary')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('mandatory')
                    ->label('Required')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('hidden')
                    ->label('Hidden')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
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
            ->defaultSort('section_id')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                SelectFilter::make('section_name')
                    ->label('Section Name')
                    ->options(SectionsInfo::pluck('section_name', 'section_name')->toArray())
                    ->searchable(),

                SelectFilter::make('type')
                    ->label('Question Type')
                    ->options([
                        'string' => 'String',
                        'select' => 'Select',
                        'multiple' => 'Multiple Select',
                        'date' => 'Date',
                    ])
                    ->multiple(),

                TernaryFilter::make('mandatory')
                    ->label('Mandatory')
                    ->placeholder('All questions')
                    ->trueLabel('Mandatory only')
                    ->falseLabel('Optional only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('mandatory', true),
                        false: fn (Builder $query) => $query->where('mandatory', false),
                    ),

                TernaryFilter::make('hidden')
                    ->label('Visibility')
                    ->placeholder('All questions')
                    ->trueLabel('Hidden only')
                    ->falseLabel('Visible only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('hidden', true),
                        false: fn (Builder $query) => $query->where('hidden', false),
                    ),
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
                    ->modalHeading('Question Details')
                    ->modalWidth('3xl'),
                EditAction::make()->icon('heroicon-o-pencil'),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->after(function () {
                        Cache::forget('questions_count');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('toggleMandatory')
                        ->label('Toggle Mandatory')
                        ->icon('heroicon-o-check-circle')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['mandatory' => ! $record->mandatory]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Mandatory status toggled'),
                    BulkAction::make('toggleHidden')
                        ->label('Toggle Hidden')
                        ->icon('heroicon-o-eye-slash')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['hidden' => ! $record->hidden]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Visibility status toggled'),
                    BulkAction::make('changeType')
                        ->label('Change Type')
                        ->icon('heroicon-o-pencil-square')
                        ->color('success')
                        ->form([
                            Select::make('type')
                                ->label('Question Type')
                                ->options([
                                    'string' => 'String',
                                    'select' => 'Select',
                                    'multiple' => 'Multiple Select',
                                    'date' => 'Date',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each->update(['type' => $data['type']]);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Question type changed'),
                    DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('questions_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->emptyStateHeading('No questions yet')
            ->emptyStateDescription('Medical form questions will appear here.')
            ->emptyStateIcon('heroicon-o-question-mark-circle');
    }

    public static function getRelations(): array
    {
        return [
            // Add relevant relationships
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestions::route('/'),
            'create' => CreateQuestions::route('/create'),
            'view' => ViewQuestions::route('/{record}'),
            'edit' => EditQuestions::route('/{record}/edit'),
        ];
    }
}
