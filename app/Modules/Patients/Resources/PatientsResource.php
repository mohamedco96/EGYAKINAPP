<?php

namespace App\Modules\Patients\Resources;

use App\Jobs\ExportPatientsJob;
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

class PatientsResource extends Resource
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
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->prefix('#')
                    ->size('sm')
                    ->weight('bold'),

                Tables\Columns\Layout\Stack::make([
                    TextColumn::make('doctor.name')
                        ->searchable(['name', 'email'])
                        ->sortable()
                        ->limit(25)
                        ->tooltip(function (TextColumn $column): ?string {
                            $state = $column->getState();

                            return strlen($state) > 25 ? $state : null;
                        })
                        ->placeholder('Unassigned')
                        ->icon('heroicon-m-user-circle')
                        ->weight('medium')
                        ->size('sm'),

                    TextColumn::make('doctor.email')
                        ->searchable()
                        ->limit(30)
                        ->tooltip(function (TextColumn $column): ?string {
                            $state = $column->getState();

                            return strlen($state) > 30 ? $state : null;
                        })
                        ->placeholder('No email')
                        ->icon('heroicon-m-envelope')
                        ->size('xs')
                        ->color('gray'),
                ])
                    ->space(1)
                    ->label('Assigned Doctor'),

                Tables\Columns\Layout\Stack::make([
                    TextColumn::make('answers_count')
                        ->badge()
                        ->color(fn ($state) => match (true) {
                            $state >= 50 => 'success',
                            $state >= 20 => 'warning',
                            default => 'danger',
                        })
                        ->suffix(' answers')
                        ->counts('answers')
                        ->sortable()
                        ->size('sm'),

                    TextColumn::make('completion_percentage')
                        ->getStateUsing(function ($record) {
                            $totalQuestions = Cache::remember('total_questions_count', 3600, fn () => Questions::count());
                            if ($totalQuestions === 0) {
                                return '0%';
                            }

                            $percentage = round(($record->answers_count / $totalQuestions) * 100, 1);

                            return $percentage.'%';
                        })
                        ->badge()
                        ->color(fn ($state) => match (true) {
                            (float) str_replace('%', '', $state) >= 70 => 'success',
                            (float) str_replace('%', '', $state) >= 30 => 'warning',
                            default => 'danger',
                        })
                        ->size('xs')
                        ->suffix(' complete'),
                ])
                    ->space(1)
                    ->label('Progress')
                    ->alignCenter(),

                TextColumn::make('sections_answered')
                    ->label('Sections')
                    ->getStateUsing(function ($record) {
                        return Cache::remember("patient_{$record->id}_sections", 300, function () use ($record) {
                            return $record->answers()
                                ->join('questions', 'answers.question_id', '=', 'questions.id')
                                ->distinct('questions.section_id')
                                ->count('questions.section_id');
                        });
                    })
                    ->badge()
                    ->color('info')
                    ->suffix(' sections')
                    ->alignCenter()
                    ->size('sm'),

                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\IconColumn::make('hidden')
                        ->boolean()
                        ->trueIcon('heroicon-m-eye-slash')
                        ->falseIcon('heroicon-m-eye')
                        ->trueColor('danger')
                        ->falseColor('success')
                        ->tooltip(fn ($state) => $state ? 'Hidden Patient' : 'Active Patient')
                        ->size('sm'),

                    TextColumn::make('status_text')
                        ->getStateUsing(fn ($record) => $record->hidden ? 'Hidden' : 'Active')
                        ->badge()
                        ->color(fn ($record) => $record->hidden ? 'danger' : 'success')
                        ->size('xs'),
                ])
                    ->space(1)
                    ->label('Status')
                    ->alignCenter(),

                Tables\Columns\Layout\Stack::make([
                    TextColumn::make('created_at')
                        ->dateTime('M j, Y')
                        ->sortable()
                        ->icon('heroicon-m-calendar-days')
                        ->size('sm')
                        ->weight('medium'),

                    TextColumn::make('created_at')
                        ->since()
                        ->size('xs')
                        ->color('gray')
                        ->prefix('• '),
                ])
                    ->space(1)
                    ->label('Registered')
                    ->tooltip(fn ($record) => $record->created_at?->format('F j, Y \a\t g:i A')),

                TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->since()
                    ->sortable()
                    ->icon('heroicon-m-clock')
                    ->size('sm')
                    ->color('gray')
                    ->tooltip(fn ($record) => $record->updated_at?->format('F j, Y \a\t g:i A'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('Assigned Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->indicator('Doctor'),

                Tables\Filters\TernaryFilter::make('hidden')
                    ->label('Patient Status')
                    ->placeholder('All Patients')
                    ->trueLabel('Hidden Patients')
                    ->falseLabel('Active Patients')
                    ->native(false)
                    ->indicator('Status'),

                Tables\Filters\Filter::make('registration_period')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\DatePicker::make('registered_from')
                                    ->label('Registered From')
                                    ->placeholder('Select start date')
                                    ->native(false),
                                \Filament\Forms\Components\DatePicker::make('registered_until')
                                    ->label('Registered Until')
                                    ->placeholder('Select end date')
                                    ->native(false),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['registered_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['registered_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicator('Registration Period'),

                Tables\Filters\Filter::make('answers_range')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('min_answers')
                                    ->label('Minimum Answers')
                                    ->numeric()
                                    ->placeholder('e.g., 10'),
                                \Filament\Forms\Components\TextInput::make('max_answers')
                                    ->label('Maximum Answers')
                                    ->numeric()
                                    ->placeholder('e.g., 100'),
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
                    })
                    ->indicator('Answer Count'),

                Tables\Filters\Filter::make('completion_rate')
                    ->form([
                        \Filament\Forms\Components\Select::make('completion_level')
                            ->label('Completion Level')
                            ->options([
                                'high' => 'High (≥70%)',
                                'medium' => 'Medium (30-69%)',
                                'low' => 'Low (<30%)',
                            ])
                            ->placeholder('Select completion level'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['completion_level']) {
                            return $query;
                        }

                        $totalQuestions = Questions::count();
                        if ($totalQuestions === 0) {
                            return $query;
                        }

                        $operator = match ($data['completion_level']) {
                            'high' => '>=',
                            'medium' => '>=',
                            'low' => '<',
                            default => '>='
                        };

                        $threshold = match ($data['completion_level']) {
                            'high' => round($totalQuestions * 0.7),
                            'medium' => round($totalQuestions * 0.3),
                            'low' => round($totalQuestions * 0.3),
                            default => 0
                        };

                        return $query->withCount('answers')->having('answers_count', $operator, $threshold);
                    })
                    ->indicator('Completion'),

                Tables\Filters\Filter::make('recent_activity')
                    ->form([
                        \Filament\Forms\Components\Select::make('activity_period')
                            ->label('Recent Activity')
                            ->options([
                                '1' => 'Last 24 hours',
                                '7' => 'Last week',
                                '30' => 'Last month',
                                '90' => 'Last 3 months',
                            ])
                            ->placeholder('Select time period'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['activity_period']) {
                            return $query;
                        }

                        $days = (int) $data['activity_period'];

                        return $query->where('updated_at', '>=', now()->subDays($days));
                    })
                    ->indicator('Recent Activity'),

                Tables\Filters\SelectFilter::make('has_doctor')
                    ->label('Doctor Assignment')
                    ->options([
                        'assigned' => 'Has Assigned Doctor',
                        'unassigned' => 'No Doctor Assigned',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'assigned' => $query->whereNotNull('doctor_id'),
                            'unassigned' => $query->whereNull('doctor_id'),
                            default => $query
                        };
                    })
                    ->indicator('Assignment'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Quick View')
                    ->modalHeading(fn ($record) => "Patient #{$record->id} Overview")
                    ->modalContent(view('filament.patients.view-modal'))
                    ->modalWidth('6xl'),

                Tables\Actions\Action::make('viewFull')
                    ->label('Full Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => static::getUrl('view', ['record' => $record])),

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
                    ->requiresConfirmation()
                    ->modalHeading('Export All Patients')
                    ->modalDescription('This will export all patient data including answers to all questions. The process may take a few minutes for large datasets.')
                    ->modalSubmitActionLabel('Start Export')
                    ->action(function () {
                        return static::startOptimizedExport();
                    }),

                Tables\Actions\Action::make('clearCache')
                    ->label('Clear Cache')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->tooltip('Clear all patient-related cached data')
                    ->requiresConfirmation()
                    ->modalHeading('Clear Patient Cache')
                    ->modalDescription('This will clear all cached statistics and patient data. Are you sure?')
                    ->action(function () {
                        Cache::forget('all_questions');
                        Cache::forget('patients_count');
                        Cache::forget('patients_stats');
                        Cache::forget('total_questions_count');

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

                Tables\Actions\Action::make('refreshStats')
                    ->label('Refresh Statistics')
                    ->icon('heroicon-o-chart-bar-square')
                    ->color('info')
                    ->tooltip('Refresh all statistics widgets')
                    ->action(function () {
                        Cache::forget('patients_stats');

                        Notification::make()
                            ->title('Statistics Refreshed')
                            ->body('Patient statistics have been refreshed.')
                            ->success()
                            ->send();

                        return redirect()->to(request()->url());
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
                // Create action removed as requested
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->poll('30s')
            ->deferLoading()
            ->emptyStateHeading('No patients found')
            ->emptyStateDescription('Get started by creating your first patient record.')
            ->emptyStateIcon('heroicon-o-users');
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
            'view' => Pages\ViewPatient::route('/{record}'),
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

    public static function startOptimizedExport()
    {
        try {
            $timestamp = time().'_'.uniqid();
            $filename = "patients_export_{$timestamp}.xlsx";
            $userId = auth()->id();

            // Check patient count to determine processing method
            $patientCount = Patients::count();

            if ($patientCount > 1000) {
                // Use background job for large datasets
                Cache::put('export_progress_'.$filename, [
                    'percentage' => 0,
                    'message' => 'Starting background export...',
                    'updated_at' => now(),
                ], 3600);

                ExportPatientsJob::dispatch($filename, 100, $userId);

                Notification::make()
                    ->success()
                    ->title('Export Started')
                    ->body('Large dataset detected. Export is processing in background. You will be notified when ready.')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('checkProgress')
                            ->label('Check Progress')
                            ->url('/export/progress/'.$filename, shouldOpenInNewTab: true),
                    ])
                    ->persistent()
                    ->send();

                return;
            } else {
                // Process immediately for smaller datasets
                $result = static::exportAllPatientsSync();

                if ($result['success']) {
                    Notification::make()
                        ->success()
                        ->title('Export Completed')
                        ->body('Your export is ready for download.')
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('download')
                                ->label('Download')
                                ->url($result['file_url'], shouldOpenInNewTab: true),
                        ])
                        ->send();
                } else {
                    Notification::make()
                        ->danger()
                        ->title('Export Failed')
                        ->body($result['message'])
                        ->send();
                }
            }

        } catch (\Exception $e) {
            Log::error('Error starting optimized export: '.$e->getMessage());

            Notification::make()
                ->danger()
                ->title('Export Failed')
                ->body('Failed to start export: '.$e->getMessage())
                ->send();
        }
    }

    public static function exportAllPatientsSync()
    {
        try {
            $questions = Cache::remember('all_questions', 3600, function () {
                return Questions::select(['id', 'question'])->orderBy('id')->get();
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
                        $query->select(['id', 'patient_id', 'question_id', 'answer'])
                            ->orderBy('question_id');
                    }])
                        ->select(['id', 'doctor_id', 'created_at', 'updated_at'])
                        ->orderBy('id')
                        ->get();
                }

                public function headings(): array
                {
                    $headings = [
                        'Patient ID',
                        'Doctor ID',
                        'Registration Date',
                        'Last Updated',
                    ];

                    foreach ($this->questions as $question) {
                        $headings[] = substr(preg_replace('/[^\w\s-]/', '', $question->question), 0, 100);
                    }

                    return $headings;
                }

                public function map($patient): array
                {
                    $data = [
                        $patient->id,
                        $patient->doctor_id,
                        $patient->created_at?->format('Y-m-d H:i:s'),
                        $patient->updated_at?->format('Y-m-d H:i:s'),
                    ];

                    // Create lookup for faster access
                    $answerLookup = $patient->answers->keyBy('question_id');

                    foreach ($this->questions as $question) {
                        $answer = $answerLookup->get($question->id);

                        if ($answer && $answer->answer) {
                            if (is_array($answer->answer)) {
                                $filteredAnswer = array_filter($answer->answer, function ($value) {
                                    return ! is_null($value) && $value !== '';
                                });
                                $data[] = ! empty($filteredAnswer) ? implode(', ', $filteredAnswer) : '';
                            } else {
                                $data[] = (string) $answer->answer;
                            }
                        } else {
                            $data[] = '';
                        }
                    }

                    return $data;
                }
            };

            $timestamp = time().'_'.uniqid();
            $filename = "patients_export_{$timestamp}.xlsx";

            Excel::store($export, 'exports/'.$filename, 'public');
            $fileUrl = config('app.url').'/storage/exports/'.$filename;

            Log::info('Successfully exported all patients to Excel (sync).', ['file_url' => $fileUrl]);

            return [
                'success' => true,
                'file_url' => $fileUrl,
                'message' => 'Export completed successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Error exporting patients to Excel (sync): '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to export data: '.$e->getMessage(),
            ];
        }
    }
}
