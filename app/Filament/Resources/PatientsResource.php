<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientsResource\Pages;
use App\Filament\Resources\PatientsResource\RelationManagers;
use App\Models\Patients;
use App\Models\Questions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PatientsResource extends Resource
{
    protected static ?string $model = Patients::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Patients';

    protected static ?string $navigationLabel = 'Patients info.';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(array_merge(
                [
                    TextColumn::make('id')->label('ID'),
                    TextColumn::make('doctor_id')->label('Doctor ID'),
                ],
                self::questionColumns()
            ))
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    protected static function questionColumns(): array
    {
        $questions = Questions::all();
        $columns = [];

        foreach ($questions as $question) {
            $columns[] = TextColumn::make("answers.{$question->id}.answer")
                ->label($question->question)
                //->sortable()
                ->searchable()
                ->getStateUsing(function ($record) use ($question) {
                    $answer = $record->answers->firstWhere('question_id', $question->id);
                    return $answer ? $answer->answer : null;
                });
        }

        return $columns;
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
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatients::route('/create'),
            'edit' => Pages\EditPatients::route('/{record}/edit'),
        ];
    }
}
