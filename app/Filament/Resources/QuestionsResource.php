<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionsResource\Pages;
use App\Modules\Questions\Models\Questions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class QuestionsResource extends Resource
{
    protected static ?string $model = Questions::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'Questions';

    protected static ?string $navigationGroup = '📊 Medical Data';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('questions_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('section_id')
                    ->required(), // Make sure it's required

                Forms\Components\Select::make('section_name')
                    ->label('Section Name')
                    ->options(function () {
                        return \App\Models\SectionsInfo::pluck('section_name', 'section_name')->toArray();
                    })
                    ->reactive()
                    ->required()
                    ->helperText('Select the section by name')
                    ->afterStateUpdated(function ($set, $get, $state) {
                        // Set section_id based on the selected section_name
                        $sectionId = \App\Models\SectionsInfo::where('section_name', $state)->value('id');
                        if ($sectionId) {
                            $set('section_id', $sectionId);
                        }
                    }),

                Forms\Components\TextInput::make('question')
                    ->label('Question')
                    ->required(),

                Forms\Components\TextInput::make('sort')
                    ->label('Sort Order'),

                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        'string' => 'String',
                        'select' => 'Select',
                        'multiple' => 'Multiple Select',
                        'date' => 'Date',
                    ])
                    ->reactive()
                    ->required(),

                Forms\Components\TagsInput::make('values')
                    ->label('Values')
                    ->placeholder('Enter question options')
                    ->reactive()
                    ->visible(fn ($get) => in_array($get('type'), ['select', 'multiple']))
                    ->required(),

                Forms\Components\Select::make('keyboard_type')
                    ->label('Keyboard Type')
                    ->options([
                        'text' => 'Text',
                        'number' => 'Number',
                        'email' => 'Email',
                    ]),

                Forms\Components\Radio::make('mandatory')
                    ->label('Mandatory')
                    ->required()
                    ->boolean(),

                Forms\Components\Radio::make('hidden')
                    ->label('Hidden')
                    ->boolean(),
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

                Tables\Columns\TextColumn::make('section_name')
                    ->label('Section')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('question')
                    ->label('Question')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->limit(80)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 80) {
                            return $state;
                        }
                        return null;
                    })
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
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

                Tables\Columns\TextColumn::make('values')
                    ->label('Values')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) return 'N/A';
                        $values = is_array($state) ? $state : json_decode($state, true);
                        if (!is_array($values)) return $state;
                        return implode(', ', array_slice($values, 0, 3)) . (count($values) > 3 ? '...' : '');
                    })
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (empty($state)) return null;
                        $values = is_array($state) ? $state : json_decode($state, true);
                        if (!is_array($values)) return null;
                        return implode(', ', $values);
                    }),

                TextInputColumn::make('sort')
                    ->label('Sort Order')
                    ->rules(['required', 'numeric'])
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('keyboard_type')
                    ->label('Keyboard')
                    ->badge()
                    ->color('secondary')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('mandatory')
                    ->label('Required')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\IconColumn::make('hidden')
                    ->label('Hidden')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
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
            ->defaultSort('section_id')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('section_name')
                    ->label('Section Name')
                    ->options(\App\Models\SectionsInfo::pluck('section_name', 'section_name')->toArray())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Question Type')
                    ->options([
                        'string' => 'String',
                        'select' => 'Select',
                        'multiple' => 'Multiple Select',
                        'date' => 'Date',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('mandatory')
                    ->label('Mandatory')
                    ->placeholder('All questions')
                    ->trueLabel('Mandatory only')
                    ->falseLabel('Optional only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('mandatory', true),
                        false: fn (Builder $query) => $query->where('mandatory', false),
                    ),

                Tables\Filters\TernaryFilter::make('hidden')
                    ->label('Visibility')
                    ->placeholder('All questions')
                    ->trueLabel('Hidden only')
                    ->falseLabel('Visible only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('hidden', true),
                        false: fn (Builder $query) => $query->where('hidden', false),
                    ),
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
                    ->modalHeading('Question Details')
                    ->modalWidth('3xl'),
                Tables\Actions\EditAction::make()->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->after(function () {
                        Cache::forget('questions_count');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('toggleMandatory')
                        ->label('Toggle Mandatory')
                        ->icon('heroicon-o-check-circle')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['mandatory' => !$record->mandatory]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Mandatory status toggled'),
                    Tables\Actions\BulkAction::make('toggleHidden')
                        ->label('Toggle Hidden')
                        ->icon('heroicon-o-eye-slash')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['hidden' => !$record->hidden]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Visibility status toggled'),
                    Tables\Actions\BulkAction::make('changeType')
                        ->label('Change Type')
                        ->icon('heroicon-o-pencil-square')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('type')
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
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('questions_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestions::route('/create'),
            'view' => Pages\ViewQuestions::route('/{record}'),
            'edit' => Pages\EditQuestions::route('/{record}/edit'),
        ];
    }
}
