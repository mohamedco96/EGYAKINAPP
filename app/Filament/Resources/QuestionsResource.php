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
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class QuestionsResource extends Resource
{
    protected static ?string $model = Questions::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Questions';

    protected static ?string $navigationGroup = 'App Data';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
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
                    ->toggleable()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('section_id')
                    ->label('Section ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('section_name')
                    ->label('Section Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('question')
                    ->label('Question')
                    ->sortable()
                    ->toggleable(),

//                Tables\Columns\TextColumn::make('sort')
//                    ->label('Sort Order')
//                    ->sortable()
//                    ->toggleable(),

                TextInputColumn::make('sort') // Editable column
                ->label('Sort Order')
                    ->rules(['required', 'max:255']) // Add validation if needed
                    ->placeholder('Enter a name...'),

                Tables\Columns\TextColumn::make('values')
                    ->label('Values')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('keyboard_type')
                    ->label('Keyboard Type')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\ToggleColumn::make('mandatory')
                    ->label('Mandatory')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('hidden')
                ->label('Hidden')
                ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('section_id')  // Default sort by 'section_name' in ascending order
            ->filters([
                Tables\Filters\SelectFilter::make('section_id')
                    ->label('Section ID')
                    ->options(\App\Models\SectionsInfo::pluck('id', 'id')->toArray()),

                Tables\Filters\SelectFilter::make('section_name')
                    ->label('Section Name')
                    ->options(\App\Models\SectionsInfo::pluck('section_name', 'section_name')->toArray()),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'string' => 'String',
                        'select' => 'Select',
                        'multiple' => 'Multiple Select',
                        'date' => 'Date',
                    ]),

                Tables\Filters\SelectFilter::make('keyboard_type')
                    ->label('Keyboard Type')
                    ->options([
                        'text' => 'Text',
                        'number' => 'Number',
                        'email' => 'Email',
                    ]),

                Tables\Filters\SelectFilter::make('mandatory')
                    ->label('Mandatory')
                    ->options([
                        1 => 'Yes',
                        0 => 'No',
                    ]),

                Tables\Filters\SelectFilter::make('hidden')
                ->label('Hidden')
                ->options([
                    1 => 'Yes',
                    0 => 'No',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                ExportBulkAction::make(),
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
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
            'edit' => Pages\EditQuestions::route('/{record}/edit'),
        ];
    }
}
