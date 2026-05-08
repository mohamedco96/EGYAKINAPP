<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultationResource\Pages\ListConsultations;
use App\Filament\Resources\ConsultationResource\Pages\ViewConsultation;
use App\Models\User;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Patients\Models\Patients;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ConsultationResource extends Resource
{
    protected static ?string $model = Consultation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Consultations';

    protected static string|\UnitEnum|null $navigationGroup = '💬 AI & Consultations';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('consultations_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $openCount = Cache::remember('consultations_open_count', 300, function () {
            return static::getModel()::where('is_open', true)->count();
        });

        return $openCount > 0 ? 'success' : 'gray';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Consultation Information')
                    ->description('Details about the consultation request')
                    ->schema([
                        Select::make('doctor_id')
                            ->relationship('doctor', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Requesting Doctor')
                            ->getSearchResultsUsing(fn (string $search) => User::where(function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('lname', 'like', "%{$search}%")
                                    ->orWhere('specialty', 'like', "%{$search}%");
                            })->limit(50)->get()->pluck('full_name_with_specialty', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->full_name_with_specialty)
                            ->helperText('Doctor who is requesting the consultation'),

                        Select::make('patient_id')
                            ->relationship('patient', 'id')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Patient')
                            ->getSearchResultsUsing(fn (string $search) => Patients::where('id', 'like', "%{$search}%")
                                ->orWhereHas('doctor', function ($query) use ($search) {
                                    $query->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                })
                                ->with('doctor')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn ($patient) => [
                                    $patient->id => 'Patient #'.$patient->id.' (Doctor: '.($patient->doctor?->name ?? 'N/A').')',
                                ]))
                            ->getOptionLabelUsing(fn ($value): ?string => Patients::with('doctor')->find($value)
                                    ? 'Patient #'.$value.' (Doctor: '.(Patients::with('doctor')->find($value)?->doctor?->name ?? 'N/A').')'
                                    : 'Patient #'.$value
                            )
                            ->helperText('Patient for whom the consultation is requested'),

                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'in-progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false)
                            ->label('Status'),

                        Toggle::make('is_open')
                            ->label('Is Open')
                            ->default(true)
                            ->helperText('Whether this consultation is still accepting replies')
                            ->inline(false),

                        Textarea::make('consult_message')
                            ->required()
                            ->label('Consultation Message')
                            ->rows(6)
                            ->columnSpanFull()
                            ->maxLength(65535)
                            ->helperText('Describe the medical case and your consultation question'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['doctor', 'patient.doctor']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('doctor.name')
                    ->label('Requesting Doctor')
                    ->searchable(['users.name', 'users.lname'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name.' '.$record->doctor->lname : 'N/A')
                    ->description(fn ($record) => $record->doctor?->specialty)
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('patient_id')
                    ->label('Patient')
                    ->formatStateUsing(fn ($record) => 'Patient #'.$record->patient_id)
                    ->description(fn ($record) => $record->patient?->doctor ? 'Doctor: '.$record->patient->doctor->name : null)
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'in-progress' => 'info',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (?string $state): string => match ($state) {
                        'completed' => 'heroicon-o-check-circle',
                        'in-progress' => 'heroicon-o-arrow-path',
                        'pending' => 'heroicon-o-clock',
                        'cancelled' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('is_open')
                    ->label('Open')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-open')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('consult_message')
                    ->label('Message')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }

                        return null;
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('consultationDoctors.count')
                    ->label('Consultants')
                    ->counts('consultationDoctors')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-user-group')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'in-progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple()
                    ->searchable(),

                TernaryFilter::make('is_open')
                    ->label('Open Status')
                    ->placeholder('All consultations')
                    ->trueLabel('Open only')
                    ->falseLabel('Closed only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_open', true),
                        false: fn (Builder $query) => $query->where('is_open', false),
                    ),

                SelectFilter::make('doctor_id')
                    ->label('Requesting Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->full_name),

                SelectFilter::make('patient_id')
                    ->label('Patient')
                    ->relationship('patient', 'id')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelUsing(fn ($value): ?string => 'Patient #'.$value),

                Filter::make('created_at')
                    ->schema([
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
                            $indicators[] = 'From '.Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until '.Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(5)
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Toggle columns'),
            )
            ->persistFiltersInSession()
            ->deferFilters(false)
            ->deselectAllRecordsWhenFiltered(true)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Consultation Details')
                    ->modalWidth('4xl'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No consultations yet')
            ->emptyStateDescription('Doctor consultation requests will appear here.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
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
            'index' => ListConsultations::route('/'),
            'view' => ViewConsultation::route('/{record}'),
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
