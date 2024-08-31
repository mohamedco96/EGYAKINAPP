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
                Forms\Components\Select::make('section_id')
                    ->label('Section ID')
                    ->options([
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                        '5' => '5',
                        '6' => '6',
                        '7' => '7',
                        '8' => '8',
                        '9' => '9',
                    ])->required(),
                Forms\Components\Select::make('section_name')
                    ->label('Section Name')
                    ->options([
                        'Patient History' => 'Patient History',
                        'Complaint' => 'Complaint',
                        'Cause of AKI' => 'Cause of AKI',
                        'Risk factors for AKI' => 'Risk factors for AKI',
                        'Assessment of the patient' => 'Assessment of the patient',
                        'Medical examinations' => 'Medical examinations',
                        'Medical decision' => 'Medical decision',
                        'Outcome' => 'Outcome',
                        'Additional information' => 'Additional information',
                    ])->required(),
                Forms\Components\TextInput::make('question')->required(),
                Forms\Components\TextInput::make('values'),
                Forms\Components\TextInput::make('sort'),
                Forms\Components\Select::make('type')
                    ->options([
                        'string' => 'String',
                        'select' => 'Select',
                        'multiple' => 'Multiple Select',
                        'date' => 'Date',
                    ])->required(),
                Forms\Components\Select::make('keyboard_type')
                    ->options([
                        'text' => 'Text',
                        'number' => 'Number',
                        'email' => 'Email',
                    ]),
                Forms\Components\Radio::make('mandatory')
                    ->required()
                    ->boolean(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                Tables\Columns\TextColumn::make('section_id')->toggleable(isToggledHiddenByDefault: false)->label('Section ID')->searchable(),
                Tables\Columns\TextColumn::make('section_name')->toggleable(isToggledHiddenByDefault: false)->label('Section Name')->searchable(),
                Tables\Columns\TextColumn::make('question')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('sort')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('values')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('type')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('keyboard_type')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\ToggleColumn::make('mandatory')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('created_at')->toggleable(isToggledHiddenByDefault: false)->label('Created At'),
                Tables\Columns\TextColumn::make('updated_at')->toggleable(isToggledHiddenByDefault: false)->label('Updated At'),
            ])
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
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
                    }),
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
