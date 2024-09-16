<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionsResource\Pages;
use App\Models\Questions;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class QuestionsResource extends Resource
{
    protected static ?string $model = Questions::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Questions';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Hidden field for section_id
                Forms\Components\Select::make('section_id')  // Save section_id
                ->label('Section ID')
                    ->options(function () {
                        return \App\Models\SectionsInfo::get()->mapWithKeys(function ($section) {
                            return [$section->id => $section->id . ' : ' . $section->section_name];  // Display section_id : section_name
                        })->toArray();
                    })
                    ->reactive()  // React to changes in section selection
                    ->required(),  // Make it required

                // Fetch section names from sections_infos table and save section_name directly
                Forms\Components\Select::make('section_name')
                    ->label('Section Name')
                    ->options(function () {
                        return \App\Models\SectionsInfo::pluck('section_name', 'section_name')->toArray();  // Fetch section_name and use it for both key and value
                    })
                    ->reactive()  // React to changes in section_name
                    ->required(),

                Forms\Components\TextInput::make('question')->required(),

                Forms\Components\TextInput::make('sort'),

                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        'string' => 'String',
                        'select' => 'Select',
                        'multiple' => 'Multiple Select',
                        'date' => 'Date',
                    ])
                    ->reactive()  // React to changes in type
                    ->required(),

                // Use TagsInput and make it visible only if type is Select or Multiple Select
                Forms\Components\TagsInput::make('values')
                    ->label('Values')
                    ->placeholder('Enter question options')
                    ->reactive()  // React to changes in type
                    ->visible(fn ($get) => in_array($get('type'), ['select', 'multiple'])) // Conditional visibility
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                Tables\Columns\TextColumn::make('section_id')->toggleable(isToggledHiddenByDefault: false)->label('Section ID')
                    ->sortable()  // Make the column sortable
                    ->searchable(),
                Tables\Columns\TextColumn::make('section_name')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('Section Name')
                    ->sortable()  // Make the column sortable
                    ->searchable(),
                Tables\Columns\TextColumn::make('question')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('sort')->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('values')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('type')->toggleable(isToggledHiddenByDefault: false)
                    ->sortable()  // Make the column sortable
                    ->searchable(),
                Tables\Columns\TextColumn::make('keyboard_type')->toggleable(isToggledHiddenByDefault: false)
                    ->sortable()  // Make the column sortable
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('mandatory')->toggleable(isToggledHiddenByDefault: false)
                    ->sortable()  // Make the column sortable
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')->toggleable(isToggledHiddenByDefault: false)->label('Created At'),
                Tables\Columns\TextColumn::make('updated_at')->toggleable(isToggledHiddenByDefault: false)->label('Updated At'),
            ])
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->defaultSort('section_id')  // Default sort by section_name in ascending order
            ->filters([
                // Filter by section_id
                Tables\Filters\SelectFilter::make('section_id')
                    ->label('Section ID')
                    ->options(function () {
                        return \App\Models\SectionsInfo::pluck('id', 'id')->toArray(); // Fetch section IDs
                    }),

                // Filter by section_name
                Tables\Filters\SelectFilter::make('section_name')
                    ->label('Section Name')
                    ->options(function () {
                        return \App\Models\SectionsInfo::pluck('section_name', 'section_name')->toArray(); // Fetch section names
                    }),

                // Filter by type
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'string' => 'String',
                        'select' => 'Select',
                        'multiple' => 'Multiple Select',
                        'date' => 'Date',
                    ]),

                // Filter by keyboard_type
                Tables\Filters\SelectFilter::make('keyboard_type')
                    ->label('Keyboard Type')
                    ->options([
                        'text' => 'Text',
                        'number' => 'Number',
                        'email' => 'Email',
                    ]),

                // Filter by mandatory (boolean)
                Tables\Filters\SelectFilter::make('mandatory')
                    ->label('Mandatory')
                    ->options([
                        1 => 'Yes',  // True
                        0 => 'No',   // False
                    ]),
            ])
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
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
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestions::route('/create'),
            'edit' => Pages\EditQuestions::route('/{record}/edit'),
        ];
    }
}
