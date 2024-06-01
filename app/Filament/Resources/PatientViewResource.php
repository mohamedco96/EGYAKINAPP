<?php

namespace App\Filament\Resources;

use App\Models\Patients;
use App\Models\Questions;
use App\Models\Answers;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\ContactResource\Pages;
use Filament\Forms\Form;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Tables\Actions\Button;

class PatientViewResource extends Resource
{
    protected static ?string $model = Patients::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Patients';

    protected static ?string $navigationGroup = 'Patient Sections';

    protected static ?int $navigationSort = 8;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public function createButton(): ?Button
    {
        return null; // Return null to hide the create button
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

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
                //Tables\Actions\EditAction::make(),
                //Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make(),
                ]),
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            //'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
