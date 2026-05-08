<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientSectionAiLogResource\Pages\ListPatientSectionAiLogs;
use App\Filament\Resources\PatientSectionAiLogResource\Pages\ViewPatientSectionAiLog;
use App\Models\PatientSectionAiLog;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PatientSectionAiLogResource extends Resource
{
    protected static ?string $model = PatientSectionAiLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'AI Session Logs';

    protected static string|\UnitEnum|null $navigationGroup = '💬 AI & Consultations';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('patient_section_ai_logs_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Context')
                    ->schema([
                        Select::make('doctor_id')
                            ->relationship('doctor', 'name')
                            ->label('Doctor')
                            ->disabled(),

                        Select::make('patient_id')
                            ->relationship('patient', 'id')
                            ->label('Patient')
                            ->disabled(),

                        Select::make('section_id')
                            ->relationship('section', 'section_name')
                            ->label('Section')
                            ->disabled(),

                        TextInput::make('input_type')
                            ->label('Input Type')
                            ->disabled(),
                    ])->columns(2),

                Section::make('AI Interaction')
                    ->schema([
                        Textarea::make('extracted_text')
                            ->label('Extracted Text (Whisper / OCR)')
                            ->rows(5)
                            ->columnSpanFull()
                            ->disabled(),

                        Textarea::make('prompt')
                            ->label('Prompt Sent to AI')
                            ->rows(8)
                            ->columnSpanFull()
                            ->disabled(),
                    ]),

                Section::make('AI Response')
                    ->schema([
                        KeyValue::make('response')
                            ->label('Extracted Values (JSON)')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['doctor', 'section', 'patient']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->formatStateUsing(fn ($record) => trim(($record->doctor?->name ?? '').' '.($record->doctor?->lname ?? '')) ?: 'N/A')
                    ->description(fn ($record) => $record->doctor?->specialty)
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('patient_id')
                    ->label('Patient')
                    ->formatStateUsing(fn ($state) => 'Patient #'.$state)
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('section.section_name')
                    ->label('Section')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('input_type')
                    ->label('Input Type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'audio' => 'success',
                        'image' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state) => match ($state) {
                        'audio' => 'heroicon-o-microphone',
                        'image' => 'heroicon-o-photo',
                        default => null,
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('extracted_text')
                    ->label('Extracted Text')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->extracted_text)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('response')
                    ->label('Response Items')
                    ->getStateUsing(fn ($record) => is_array($record->response) ? count($record->response).' fields' : '—')
                    ->badge()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Logged At')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->deferFilters(false)
            ->filters([
                SelectFilter::make('input_type')
                    ->label('Input Type')
                    ->options([
                        'audio' => 'Audio (Voice)',
                        'image' => 'Image / PDF',
                    ])
                    ->placeholder('All types'),

                SelectFilter::make('section_id')
                    ->label('Section')
                    ->relationship('section', 'section_name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All sections'),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')->label('From'),
                        DatePicker::make('created_until')->label('Until'),
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
            ->filtersFormColumns(3)
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action->button()->label('Toggle columns'),
            )
            ->deselectAllRecordsWhenFiltered(true)
            ->filtersTriggerAction(
                fn (Action $action) => $action->button()->label('Filter'),
            )
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('AI Form Log')
                    ->modalWidth('5xl'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No AI form logs yet')
            ->emptyStateDescription('Logs are recorded when a doctor uses AI voice or image to fill a section.')
            ->emptyStateIcon('heroicon-o-cpu-chip');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatientSectionAiLogs::route('/'),
            'view' => ViewPatientSectionAiLog::route('/{record}'),
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
