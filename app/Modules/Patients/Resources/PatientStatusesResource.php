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

class PatientStatusesResource extends Resource
{
    protected static ?string $model = PatientStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Patients';

    protected static ?string $navigationLabel = 'Sections Status';

    protected static ?int $navigationSort = 3;

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

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Assigned Doctor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unassigned')
                    ->limit(25)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 25 ? $state : null;
                    })
                    ->icon('heroicon-m-user-circle'),

                Tables\Columns\TextColumn::make('section_name')
                    ->label('Section')
                    ->getStateUsing(function ($record) {
                        if (str_starts_with($record->key, 'section_')) {
                            $sectionId = str_replace('section_', '', $record->key);

                            return Cache::remember("section_name_{$sectionId}", 3600, function () use ($sectionId) {
                                $section = SectionsInfo::find($sectionId);

                                return $section?->section_name ?? "Section {$sectionId}";
                            });
                        }

                        return ucfirst(str_replace('_', ' ', $record->key));
                    })
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-folder'),

                Tables\Columns\IconColumn::make('status')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-m-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($state) => $state ? 'Completed' : 'Pending')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status_badge')
                    ->label('Progress')
                    ->getStateUsing(fn ($record) => $record->status ? 'Completed' : 'Pending')
                    ->badge()
                    ->color(fn ($record) => $record->status ? 'success' : 'warning')
                    ->icon(fn ($record) => $record->status ? 'heroicon-m-check' : 'heroicon-m-clock'),

                Tables\Columns\TextColumn::make('all_sections')
                    ->label('All Patient Sections')
                    ->getStateUsing(function ($record) {
                        return Cache::remember("patient_sections_{$record->patient_id}", 300, function () use ($record) {
                            $sections = PatientStatus::where('patient_id', $record->patient_id)
                                ->where('key', 'LIKE', 'section_%')
                                ->get()
                                ->map(function ($section) {
                                    $sectionId = str_replace('section_', '', $section->key);
                                    $sectionInfo = SectionsInfo::find($sectionId);
                                    $name = $sectionInfo?->section_name ?? "Section {$sectionId}";
                                    $status = $section->status ? '✅' : '❌';

                                    return "{$status} {$name}";
                                });

                            return $sections->join(' | ');
                        });
                    })
                    ->html()
                    ->limit(100)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        return $column->getState();
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->updated_at?->format('F j, Y \a\t g:i:s A'))
                    ->since()
                    ->color('gray')
                    ->size('sm'),
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

                Tables\Actions\Action::make('toggleStatus')
                    ->label('Toggle Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function ($record) {
                        $record->update(['status' => ! $record->status]);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Status Updated')
                            ->body('Section status has been toggled successfully.')
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markCompleted')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => true]);
                            }
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Sections Updated')
                                ->body('Selected sections marked as completed.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('markPending')
                        ->label('Mark as Pending')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => false]);
                            }
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Sections Updated')
                                ->body('Selected sections marked as pending.')
                                ->send();
                        })
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
        return parent::getTableQuery()
            ->with(['patient.doctor', 'doctor'])
            ->where('key', 'LIKE', 'section_%')
            ->orderBy('patient_id', 'asc')
            ->orderBy('key', 'asc');
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
