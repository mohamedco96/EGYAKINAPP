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
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

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
            ->headerActions([
                Tables\Actions\Action::make('exportAll')
                    ->label('Export All Patients')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function () {
                        $result = static::exportAllPatients();
                        
                        if ($result['success']) {
                            // Show success notification
                            Notification::make()
                                ->success()
                                ->title('Export Started')
                                ->body('Your export is being downloaded in a new tab.')
                                ->send();

                            // Return the file URL to be handled by JavaScript
                            return [
                                'url' => $result['file_url'],
                                'openUrlInNewTab' => true
                            ];
                        }
                        
                        // Show error notification
                        Notification::make()
                            ->title('Export Failed')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    })
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

    public static function exportAllPatients()
    {
        try {
            // Get all questions from cache
            $questions = Cache::remember('all_questions', now()->addHour(), function() {
                return Questions::query()
                    ->select(['id', 'question'])
                    ->get();
            });

            // Create the export class
            $export = new class($questions) implements FromCollection, WithHeadings, WithMapping {
                private $questions;

                public function __construct($questions)
                {
                    $this->questions = $questions;
                }

                public function collection()
                {
                    return Patients::with(['answers' => function($query) {
                        $query->select(['id', 'patient_id', 'question_id', 'answer']);
                    }])->get();
                }

                public function headings(): array
                {
                    $headings = [
                        'ID',
                        'Doctor ID',
                    ];

                    foreach ($this->questions as $question) {
                        $headings[] = $question->question;
                    }

                    return $headings;
                }

                public function map($record): array
                {
                    $data = [
                        $record->id,
                        $record->doctor_id,
                    ];

                    foreach ($this->questions as $question) {
                        $data[] = $record->answers->firstWhere('question_id', $question->id)?->answer;
                    }

                    return $data;
                }
            };

            // Generate a unique filename with timestamp
            $timestamp = time() . '_' . uniqid();
            $filename = "patients_export_{$timestamp}.xlsx";

            // Store the Excel file in the public disk
            Excel::store($export, 'exports/' . $filename, 'public');

            // Construct the full URL for the exported file
            $fileUrl = config('app.url') . '/storage/exports/' . $filename;

            // Log successful export
            Log::info('Successfully exported all patients to Excel.', ['file_url' => $fileUrl]);

            return [
                'success' => true,
                'file_url' => $fileUrl,
                'message' => 'Export completed successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error exporting patients to Excel: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to export data: ' . $e->getMessage()
            ];
        }
    }
}