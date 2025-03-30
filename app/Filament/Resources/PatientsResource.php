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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

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
                    ExportBulkAction::make()
                        ->withColumns(function () {
                            $columns = [
                                'id' => fn ($record) => $record->id,
                                'doctor_id' => fn ($record) => $record->doctor_id,
                            ];

                            // Get questions from cache
                            $questions = Cache::remember('all_questions', now()->addHour(), function() {
                                return Questions::query()
                                    ->select(['id', 'question'])
                                    ->get();
                            });

                            // Add question columns
                            foreach ($questions as $question) {
                                $columns["question_{$question->id}"] = function ($record) use ($question) {
                                    return $record->answers->firstWhere('question_id', $question->id)?->answer;
                                };
                            }

                            return $columns;
                        }),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    protected static function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['answers' => function($query) {
                $query->select(['id', 'patient_id', 'question_id', 'answer']);
            }]);
    }

    protected static function questionColumns(): array
    {
        // Cache questions for 1 hour to prevent repeated queries
        $questions = Cache::remember('all_questions', now()->addHour(), function() {
            return Questions::query()
                ->select(['id', 'question'])
                ->get();
        });

        $columns = [];

        foreach ($questions as $question) {
            $columns[] = TextColumn::make("question_{$question->id}")
                ->label($question->question)
                ->searchable(
                    query: fn (Builder $query, string $search) => $query->whereHas('answers', 
                        fn ($q) => $q->where('question_id', $question->id)
                                    ->where('answer', 'like', "%{$search}%")
                    )
                )
                ->getStateUsing(function ($record) use ($question) {
                    return $record->answers->firstWhere('question_id', $question->id)?->answer;
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