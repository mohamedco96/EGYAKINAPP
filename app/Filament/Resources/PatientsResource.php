<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientsResource\Pages;
use App\Models\Patients;
use App\Models\Questions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PatientsResource extends Resource
{
    protected static ?string $model = Patients::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Patients';
    protected static ?string $navigationLabel = 'Patients info.';
    protected static ?int $navigationSort = 2;

    // Optimized navigation badge with cache
    public static function getNavigationBadge(): ?string
    {
        try {
            return Cache::remember('patients_count', now()->addHours(6), function() {
                return static::getModel()::count();
            });
        } catch (\Exception $e) {
            Log::error('Failed to get patients count', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(self::getTableColumns())
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([Tables\Actions\CreateAction::make()])
            ->paginated([10, 25, 50, 100, 'all']) // Includes 'all' option
            ->deferLoading() // Helps with initial load
            ->query(
                fn (Builder $query) => $query->with([
                    'answers' => fn ($q) => $q->select(['id', 'patient_id', 'question_id', 'answer'])
                                             ->with(['question' => fn ($q) => $q->select(['id', 'question'])])
                ])
            );
    }

    protected static function getTableColumns(): array
    {
        return array_merge(
            [
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('doctor_id')
                    ->label('Doctor ID')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date Added')
                    ->dateTime()
                    ->sortable(),
            ],
            self::getQuestionColumns()
        );
    }

    protected static function getQuestionColumns(): array
    {
        try {
            $questions = Cache::remember('questions_columns', now()->addDay(), function() {
                return Questions::query()
                    ->select(['id', 'question'])
                    ->orderBy('id')
                    ->get()
                    ->keyBy('id');
            });

            $columns = [];
            $chunkSize = 25; // Process questions in chunks

            foreach ($questions->chunk($chunkSize) as $chunk) {
                foreach ($chunk as $question) {
                    $columns[] = TextColumn::make("answer_{$question->id}")
                        ->label(Str::limit($question->question, 25))
                        ->searchable(
                            fn (Builder $query, string $search) => $query->whereHas('answers',
                                fn ($q) => $q->where('question_id', $question->id)
                                            ->where('answer', 'like', "%{$search}%")
                            )
                        )
                        ->getStateUsing(
                            fn ($record) => $record->answers->firstWhere('question_id', $question->id)?->answer
                        )
                        ->tooltip($question->question)
                        ->wrap()
                        ->sortable(
                            fn (Builder $query, string $direction) => $query->orderBy(
                                \App\Models\Answer::select('answer')
                                    ->whereColumn('answers.patient_id', 'patients.id')
                                    ->where('question_id', $question->id)
                                    ->limit(1),
                                $direction
                            )
                        );
                }
            }

            return $columns;
        } catch (\Exception $e) {
            Log::error('Failed to generate question columns', ['error' => $e]);
            return [];
        }
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatients::route('/create'),
            'edit' => Pages\EditPatients::route('/{record}/edit'),
        ];
    }

    // Add this method to prevent memory issues with 'all' option
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(request()->has('all'), function ($query) {
                Log::warning('Loading all patients records - this may impact performance');
                return $query;
            });
    }
}