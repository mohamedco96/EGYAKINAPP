<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AIConsultationResource\Pages;
use App\Modules\Chat\Models\AIConsultation;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AIConsultationResource extends Resource
{
    protected static ?string $model = AIConsultation::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'AI Consultations';

    protected static ?string $navigationGroup = '💬 AI & Consultations';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('ai_consultations_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('AI Consultation Information')
                    ->description('Details about the AI consultation')
                    ->schema([
                        Forms\Components\Select::make('doctor_id')
                            ->relationship('doctor', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Doctor')
                            ->getSearchResultsUsing(fn (string $search) => \App\Models\User::where(function($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('lname', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            })->limit(50)->get()->pluck('full_name_with_email', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => \App\Models\User::find($value)?->full_name_with_email)
                            ->helperText('Doctor who requested the AI consultation'),

                        Forms\Components\Select::make('patient_id')
                            ->relationship('patient', 'id')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Patient')
                            ->getSearchResultsUsing(fn (string $search) => \App\Modules\Patients\Models\Patients::where('id', 'like', "%{$search}%")
                                ->orWhereHas('doctor', function($query) use ($search) {
                                    $query->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                })
                                ->with('doctor')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn ($patient) => [
                                    $patient->id => 'Patient #' . $patient->id . ' (Doctor: ' . ($patient->doctor?->name ?? 'N/A') . ')'
                                ]))
                            ->getOptionLabelUsing(fn ($value): ?string =>
                                \App\Modules\Patients\Models\Patients::with('doctor')->find($value)
                                    ? 'Patient #' . $value . ' (Doctor: ' . (\App\Modules\Patients\Models\Patients::with('doctor')->find($value)?->doctor?->name ?? 'N/A') . ')'
                                    : 'Patient #' . $value
                            )
                            ->helperText('Patient for whom the consultation is requested'),

                        Forms\Components\Textarea::make('question')
                            ->required()
                            ->label('Question to AI')
                            ->rows(4)
                            ->columnSpanFull()
                            ->maxLength(65535)
                            ->helperText('The medical question asked to the AI'),

                        Forms\Components\Textarea::make('response')
                            ->label('AI Response')
                            ->rows(6)
                            ->columnSpanFull()
                            ->maxLength(65535)
                            ->helperText('The AI-generated response (auto-filled by system)')
                            ->disabled()
                            ->dehydrated(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable(['users.name', 'users.lname'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name . ' ' . $record->doctor->lname : 'N/A')
                    ->description(fn ($record) => $record->doctor?->specialty)
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('patient_id')
                    ->label('Patient')
                    ->formatStateUsing(fn ($record) => 'Patient #' . $record->patient_id)
                    ->description(fn ($record) => $record->patient?->doctor ? 'Doctor: ' . $record->patient->doctor->name : null)
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('question')
                    ->label('Question')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }
                        return null;
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('response')
                    ->label('AI Response')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }
                        return null;
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\IconColumn::make('response')
                    ->label('Has Response')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !empty($record->response))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelUsing(fn ($value): ?string => \App\Models\User::find($value)?->full_name),

                Tables\Filters\SelectFilter::make('patient_id')
                    ->label('Patient')
                    ->relationship('patient', 'id')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelUsing(fn ($value): ?string => 'Patient #' . $value),

                Tables\Filters\Filter::make('has_response')
                    ->label('Has AI Response')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('response')->where('response', '!=', '')),

                Tables\Filters\Filter::make('no_response')
                    ->label('No AI Response')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        $q->whereNull('response')->orWhere('response', '=', '');
                    })),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('From'),
                        DatePicker::make('created_until')
                            ->label('Until'),
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'From ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(5)
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
                Tables\Actions\ViewAction::make()
                    ->modalHeading('AI Consultation Details')
                    ->modalWidth('5xl'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No AI consultations yet')
            ->emptyStateDescription('AI consultation requests will appear here.')
            ->emptyStateIcon('heroicon-o-cpu-chip');
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
            'index' => Pages\ListAIConsultations::route('/'),
            'view' => Pages\ViewAIConsultation::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
