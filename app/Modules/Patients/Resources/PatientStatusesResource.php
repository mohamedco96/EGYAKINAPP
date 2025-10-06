<?php

namespace App\Modules\Patients\Resources;

use App\Models\SectionsInfo;
use App\Modules\Patients\Models\PatientStatus;
use App\Modules\Patients\Resources\PatientStatusesResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PatientStatusesResource extends Resource
{
    protected static ?string $model = PatientStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = '🏥 Patient Management';

    protected static ?string $navigationLabel = 'Sections Status';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('key', 'LIKE', 'section_%')->count();
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
                Tables\Columns\TextColumn::make('patient_id')
                    ->label('Patient ID')
                    ->getStateUsing(function ($record) {
                        return (string) $record->patient_id;
                    })
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->prefix('#')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('patient_name')
                    ->label('Patient Name')
                    ->getStateUsing(function ($record) {
                        return Cache::remember("patient_name_{$record->patient_id}", 300, function () use ($record) {
                            $firstAnswer = \App\Models\Answers::where('patient_id', $record->patient_id)
                                ->where('question_id', 1)
                                ->first();

                            if ($firstAnswer && is_string($firstAnswer->answer)) {
                                return trim($firstAnswer->answer, '"');
                            }

                            return 'Patient #'.$record->patient_id;
                        });
                    })
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    })
                    ->icon('heroicon-m-user')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('doctor_name')
                    ->label('Assigned Doctor')
                    ->getStateUsing(function ($record) {
                        return Cache::remember("patient_doctor_{$record->patient_id}", 300, function () use ($record) {
                            $patient = \App\Modules\Patients\Models\Patients::with('doctor')
                                ->find($record->patient_id);

                            return $patient?->doctor?->name ?? 'Unassigned';
                        });
                    })
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 25 ? $state : null;
                    })
                    ->icon('heroicon-m-user-circle'),

                Tables\Columns\TextColumn::make('all_sections_status')
                    ->label('All Sections Status')
                    ->getStateUsing(function ($record) {
                        // Ensure we have a valid patient_id
                        if (! isset($record->patient_id) || ! $record->patient_id) {
                            return 'No sections';
                        }

                        return Cache::remember("patient_all_sections_{$record->patient_id}", 300, function () use ($record) {
                            $sections = PatientStatus::where('patient_id', $record->patient_id)
                                ->where('key', 'LIKE', 'section_%')
                                ->orderBy('key')
                                ->get();

                            if ($sections->isEmpty()) {
                                return 'No sections';
                            }

                            $html = '<div class="space-y-1">';
                            foreach ($sections as $section) {
                                $sectionId = str_replace('section_', '', $section->key);
                                $sectionInfo = SectionsInfo::find($sectionId);
                                $name = $sectionInfo?->section_name ?? "Section {$sectionId}";
                                $status = $section->status ? '✅' : '❌';
                                $completed = $section->status;

                                $colorClass = $completed ? 'text-green-600 bg-green-50' : 'text-orange-600 bg-orange-50';
                                $html .= '<div class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium '.$colorClass.' mr-1 mb-1">';
                                $html .= '<span>'.$status.'</span>';
                                $html .= '<span>'.$name.'</span>';
                                $html .= '</div>';
                            }
                            $html .= '</div>';

                            return $html;
                        });
                    })
                    ->html()
                    ->wrap(),

                Tables\Columns\TextColumn::make('completion_summary')
                    ->label('Summary')
                    ->getStateUsing(function ($record) {
                        return Cache::remember("patient_completion_{$record->patient_id}", 300, function () use ($record) {
                            $sections = PatientStatus::where('patient_id', $record->patient_id)
                                ->where('key', 'LIKE', 'section_%')
                                ->get();

                            $total = $sections->count();
                            $completed = $sections->where('status', true)->count();
                            $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

                            $color = match (true) {
                                $percentage >= 80 => 'success',
                                $percentage >= 50 => 'warning',
                                default => 'danger'
                            };

                            return '<div class="text-center">
                                <div class="font-semibold text-'.$color.'-600">'.$percentage.'%</div>
                                <div class="text-xs text-gray-500">'.$completed.'/'.$total.' completed</div>
                            </div>';
                        });
                    })
                    ->html()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('last_activity')
                    ->label('Last Activity')
                    ->getStateUsing(function ($record) {
                        $lastUpdate = PatientStatus::where('patient_id', $record->patient_id)
                            ->where('key', 'LIKE', 'section_%')
                            ->latest('updated_at')
                            ->first();

                        if ($lastUpdate && $lastUpdate->updated_at) {
                            return $lastUpdate->updated_at->since();
                        }

                        return 'No activity';
                    })
                    ->sortable()
                    ->color('gray')
                    ->size('sm')
                    ->tooltip(function ($record) {
                        $lastUpdate = PatientStatus::where('patient_id', $record->patient_id)
                            ->where('key', 'LIKE', 'section_%')
                            ->latest('updated_at')
                            ->first();

                        return $lastUpdate?->updated_at?->format('F j, Y \a\t g:i:s A') ?? 'No activity';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('patient_id')
                    ->label('Patient')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        return \App\Modules\Patients\Models\Patients::with('doctor')
                            ->whereHas('answers', function ($query) use ($search) {
                                $query->where('question_id', 1)
                                    ->where('answer', 'LIKE', "%{$search}%");
                            })
                            ->orWhere('id', 'LIKE', "%{$search}%")
                            ->limit(20)
                            ->get()
                            ->mapWithKeys(function ($patient) {
                                $name = \App\Models\Answers::where('patient_id', $patient->id)
                                    ->where('question_id', 1)
                                    ->value('answer');
                                $name = $name ? trim($name, '"') : "Patient #{$patient->id}";

                                return [$patient->id => $name];
                            })
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        $name = \App\Models\Answers::where('patient_id', $value)
                            ->where('question_id', 1)
                            ->value('answer');

                        return $name ? trim($name, '"') : "Patient #{$value}";
                    }),

                Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable(),

                Tables\Filters\Filter::make('section_type')
                    ->form([
                        Forms\Components\Select::make('section')
                            ->label('Section Type')
                            ->options(function () {
                                return Cache::remember('sections_filter_options', 3600, function () {
                                    return SectionsInfo::pluck('section_name', 'id')->toArray();
                                });
                            })
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['section'],
                            fn (Builder $query, $section): Builder => $query->where('key', 'section_'.$section),
                        );
                    }),

                Tables\Filters\TernaryFilter::make('status')
                    ->label('Status')
                    ->placeholder('All statuses')
                    ->trueLabel('Completed')
                    ->falseLabel('Pending'),

                Tables\Filters\Filter::make('recent_activity')
                    ->form([
                        Forms\Components\Select::make('period')
                            ->label('Activity Period')
                            ->options([
                                'today' => 'Today',
                                'week' => 'This Week',
                                'month' => 'This Month',
                                'quarter' => 'This Quarter',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['period'],
                            function (Builder $query, $period): Builder {
                                return match ($period) {
                                    'today' => $query->whereDate('updated_at', today()),
                                    'week' => $query->where('updated_at', '>=', now()->startOfWeek()),
                                    'month' => $query->where('updated_at', '>=', now()->startOfMonth()),
                                    'quarter' => $query->where('updated_at', '>=', now()->startOfQuarter()),
                                    default => $query,
                                };
                            }
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('viewPatient')
                    ->label('View Patient')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.patients.view', ['record' => $record->patient_id]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('manageSections')
                    ->label('Manage Sections')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('warning')
                    ->form([
                        Forms\Components\Repeater::make('sections')
                            ->label('Patient Sections')
                            ->schema([
                                Forms\Components\TextInput::make('section_name')
                                    ->label('Section')
                                    ->disabled(),
                                Forms\Components\Toggle::make('status')
                                    ->label('Completed')
                                    ->inline(false),
                            ])
                            ->defaultItems(0)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ])
                    ->fillForm(function ($record) {
                        $sections = PatientStatus::where('patient_id', $record->patient_id)
                            ->where('key', 'LIKE', 'section_%')
                            ->orderBy('key')
                            ->get()
                            ->map(function ($section) {
                                $sectionId = str_replace('section_', '', $section->key);
                                $sectionInfo = SectionsInfo::find($sectionId);
                                $name = $sectionInfo?->section_name ?? "Section {$sectionId}";

                                return [
                                    'section_id' => $section->id,
                                    'section_name' => $name,
                                    'status' => $section->status,
                                ];
                            })
                            ->toArray();

                        return ['sections' => $sections];
                    })
                    ->action(function ($record, $data) {
                        foreach ($data['sections'] as $index => $sectionData) {
                            $sections = PatientStatus::where('patient_id', $record->patient_id)
                                ->where('key', 'LIKE', 'section_%')
                                ->orderBy('key')
                                ->get();

                            if (isset($sections[$index])) {
                                $sections[$index]->update(['status' => $sectionData['status']]);
                            }
                        }

                        // Clear cache
                        Cache::forget("patient_all_sections_{$record->patient_id}");
                        Cache::forget("patient_completion_{$record->patient_id}");

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Sections Updated')
                            ->body('Patient sections have been updated successfully.')
                            ->send();
                    })
                    ->modalWidth('2xl'),

                Tables\Actions\Action::make('markAllCompleted')
                    ->label('Complete All')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($record) {
                        PatientStatus::where('patient_id', $record->patient_id)
                            ->where('key', 'LIKE', 'section_%')
                            ->update(['status' => true]);

                        // Clear cache
                        Cache::forget("patient_all_sections_{$record->patient_id}");
                        Cache::forget("patient_completion_{$record->patient_id}");

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('All Sections Completed')
                            ->body('All sections for this patient have been marked as completed.')
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Complete All Sections')
                    ->modalDescription('Are you sure you want to mark all sections as completed for this patient?'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markAllPatientsCompleted')
                        ->label('Complete All Sections for Selected Patients')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                PatientStatus::where('patient_id', $record->patient_id)
                                    ->where('key', 'LIKE', 'section_%')
                                    ->update(['status' => true]);

                                // Clear cache
                                Cache::forget("patient_all_sections_{$record->patient_id}");
                                Cache::forget("patient_completion_{$record->patient_id}");
                            }

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Patients Updated')
                                ->body('All sections for selected patients have been marked as completed.')
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('markAllPatientsPending')
                        ->label('Mark All Sections as Pending for Selected Patients')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                PatientStatus::where('patient_id', $record->patient_id)
                                    ->where('key', 'LIKE', 'section_%')
                                    ->update(['status' => false]);

                                // Clear cache
                                Cache::forget("patient_all_sections_{$record->patient_id}");
                                Cache::forget("patient_completion_{$record->patient_id}");
                            }

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Patients Updated')
                                ->body('All sections for selected patients have been marked as pending.')
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->poll('30s')
            ->emptyStateHeading('No section statuses found')
            ->emptyStateDescription('Section statuses will appear here as patients complete their assessments.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }

    protected static function getTableQuery(): Builder
    {
        // Create a custom query to get unique patients with section statuses
        $patientIds = PatientStatus::where('key', 'LIKE', 'section_%')
            ->distinct()
            ->pluck('patient_id');

        return PatientStatus::query()
            ->whereIn('patient_id', $patientIds)
            ->where('key', 'LIKE', 'section_%')
            ->select('patient_id', DB::raw('MIN(id) as id'), DB::raw('MAX(updated_at) as updated_at'))
            ->groupBy('patient_id')
            ->orderBy('patient_id', 'asc');
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
            'index' => Pages\ListPatientStatuses::route('/'),
            'create' => Pages\CreatePatientStatuses::route('/create'),
            'edit' => Pages\EditPatientStatuses::route('/{record}/edit'),
        ];
    }
}
