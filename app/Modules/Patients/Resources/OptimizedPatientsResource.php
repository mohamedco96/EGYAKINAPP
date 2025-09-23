<?php

namespace App\Modules\Patients\Resources;

use App\Modules\Patients\Models\Patients;
use App\Modules\Patients\Resources\PatientsResource\Pages;
use App\Modules\Questions\Models\Questions;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Facades\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class OptimizedPatientsResource extends Resource
{
    protected static ?string $model = Patients::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = '🏥 Patient Management';

    protected static ?string $navigationLabel = 'Patients Info';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationBadge(): ?string
    {
        // Cache the count for 5 minutes to improve performance
        return Cache::remember('patients_count', 300, function () {
            return static::getModel()::count();
        });
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
            ->columns([
                TextColumn::make('id')
                    ->label('Patient ID')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->prefix('#'),

                TextColumn::make('doctor.name')
                    ->label('Assigned Doctor')
                    ->searchable(['name', 'email'])
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    }),

                TextColumn::make('doctor.email')
                    ->label('Doctor Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(25),

                TextColumn::make('answers_count')
                    ->label('Completed Answers')
                    ->badge()
                    ->color(fn ($state) => $state > 100 ? 'success' : ($state > 50 ? 'warning' : 'danger'))
                    ->counts('answers')
                    ->sortable(),

                TextColumn::make('sections_answered')
                    ->label('Sections Completed')
                    ->getStateUsing(function ($record) {
                        // Get unique sections from answers
                        return Cache::remember("patient_{$record->id}_sections", 300, function () use ($record) {
                            return $record->answers()
                                ->join('questions', 'answers.question_id', '=', 'questions.id')
                                ->distinct('questions.section_id')
                                ->count('questions.section_id');
                        });
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('hidden')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn ($state) => $state ? 'Hidden' : 'Active')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($state) => $state?->format('F j, Y \a\t g:i A')),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('hidden')
                    ->label('Status')
                    ->trueLabel('Hidden')
                    ->falseLabel('Active')
                    ->native(false),

                Tables\Filters\Filter::make('answers_count')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('min_answers')
                                    ->label('Min Answers')
                                    ->numeric(),
                                \Filament\Forms\Components\TextInput::make('max_answers')
                                    ->label('Max Answers')
                                    ->numeric(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_answers'],
                                fn (Builder $query, $min): Builder => $query->withCount('answers')
                                    ->having('answers_count', '>=', $min),
                            )
                            ->when(
                                $data['max_answers'],
                                fn (Builder $query, $max): Builder => $query->withCount('answers')
                                    ->having('answers_count', '<=', $max),
                            );
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
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
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View Details')
                    ->modalHeading(fn ($record) => "Patient #{$record->id} Details")
                    ->modalContent(view('filament.patients.view-modal'))
                    ->modalWidth('7xl'),

                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('exportPatient')
                    ->label('Export')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($record) {
                        return static::exportSinglePatient($record);
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportAll')
                    ->label('Export All Patients')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->action(function () {
                        $result = static::exportAllPatients();

                        if ($result['success']) {
                            Notification::make()
                                ->success()
                                ->title('Export Started')
                                ->body('Your export is being downloaded.')
                                ->send();

                            return redirect($result['file_url']);
                        }

                        Notification::make()
                            ->title('Export Failed')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\Action::make('clearCache')
                    ->label('Clear Cache')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function () {
                        Cache::forget('all_questions');
                        Cache::forget('patients_count');

                        // Clear patient-specific caches
                        Patients::all()->each(function ($patient) {
                            Cache::forget("patient_{$patient->id}_sections");
                        });

                        Notification::make()
                            ->success()
                            ->title('Cache Cleared')
                            ->body('All patient-related caches have been cleared.')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Export Selected'),

                    Tables\Actions\BulkAction::make('toggleStatus')
                        ->label('Toggle Status')
                        ->icon('heroicon-o-eye')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['hidden' => ! $record->hidden]);
                            }

                            Notification::make()
                                ->success()
                                ->title('Status Updated')
                                ->body('Selected patients status has been toggled.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->poll('30s');
    }

    protected static function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['doctor:id,name,email'])
            ->withCount('answers');
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

    public static function exportSinglePatient($patient)
    {
        try {
            $questions = Cache::remember('all_questions', 3600, function () {
                return Questions::select(['id', 'question'])->get();
            });

            $export = new class($questions, $patient) implements FromCollection, WithHeadings, WithMapping
            {
                private $questions;

                private $patient;

                public function __construct($questions, $patient)
                {
                    $this->questions = $questions;
                    $this->patient = $patient;
                }

                public function collection()
                {
                    return collect([$this->patient->load('answers')]);
                }

                public function headings(): array
                {
                    $headings = ['ID', 'Doctor ID'];
                    foreach ($this->questions as $question) {
                        $headings[] = $question->question;
                    }

                    return $headings;
                }

                public function map($record): array
                {
                    $data = [$record->id, $record->doctor_id];
                    foreach ($this->questions as $question) {
                        $data[] = $record->answers->firstWhere('question_id', $question->id)?->answer;
                    }

                    return $data;
                }
            };

            $filename = "patient_{$patient->id}_export_".time().'.xlsx';
            Excel::store($export, 'exports/'.$filename, 'public');

            return redirect(config('app.url').'/storage/exports/'.$filename);

        } catch (\Exception $e) {
            Log::error('Error exporting single patient: '.$e->getMessage());

            Notification::make()
                ->danger()
                ->title('Export Failed')
                ->body('Failed to export patient data.')
                ->send();
        }
    }

    public static function exportAllPatients()
    {
        try {
            $questions = Cache::remember('all_questions', 3600, function () {
                return Questions::select(['id', 'question'])->get();
            });

            $export = new class($questions) implements FromCollection, WithHeadings, WithMapping
            {
                private $questions;

                public function __construct($questions)
                {
                    $this->questions = $questions;
                }

                public function collection()
                {
                    return Patients::with(['answers' => function ($query) {
                        $query->select(['id', 'patient_id', 'question_id', 'answer']);
                    }])->get();
                }

                public function headings(): array
                {
                    $headings = ['ID', 'Doctor ID'];
                    foreach ($this->questions as $question) {
                        $headings[] = $question->question;
                    }

                    return $headings;
                }

                public function map($record): array
                {
                    $data = [$record->id, $record->doctor_id];
                    foreach ($this->questions as $question) {
                        $data[] = $record->answers->firstWhere('question_id', $question->id)?->answer;
                    }

                    return $data;
                }
            };

            $timestamp = time().'_'.uniqid();
            $filename = "patients_export_{$timestamp}.xlsx";
            Excel::store($export, 'exports/'.$filename, 'public');
            $fileUrl = config('app.url').'/storage/exports/'.$filename;

            Log::info('Successfully exported all patients to Excel.', ['file_url' => $fileUrl]);

            return [
                'success' => true,
                'file_url' => $fileUrl,
                'message' => 'Export completed successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Error exporting patients to Excel: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to export data: '.$e->getMessage(),
            ];
        }
    }
}
