<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnswersResource\Pages\CreateAnswers;
use App\Filament\Resources\AnswersResource\Pages\EditAnswers;
use App\Filament\Resources\AnswersResource\Pages\ListAnswers;
use App\Filament\Resources\AnswersResource\Pages\ViewAnswers;
use App\Models\Answers;
use App\Models\SectionsInfo;
use App\Modules\Patients\Models\Patients;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AnswersResource extends Resource
{
    protected static ?string $model = Answers::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Patient Answers';

    protected static string|\UnitEnum|null $navigationGroup = '📊 Medical Data';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('answers_count', 300, fn () => static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Answer Information')->schema([
                Select::make('patient_id')
                    ->relationship('patient', 'id')
                    ->getSearchResultsUsing(function (string $search) {
                        return Patients::query()
                            ->where('id', 'like', "%{$search}%")
                            ->orWhereHas('doctor', function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('lname', 'like', "%{$search}%");
                            })
                            ->with('doctor')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($patient) {
                                $doctorName = $patient->doctor ? $patient->doctor->name.' '.($patient->doctor->lname ?? '') : 'Unknown';

                                return [$patient->id => "Patient #{$patient->id} (Doctor: {$doctorName})"];
                            });
                    })
                    ->getOptionLabelUsing(function ($value) {
                        $patient = Patients::with('doctor')->find($value);
                        if (! $patient) {
                            return "Patient #{$value}";
                        }
                        $doctorName = $patient->doctor ? $patient->doctor->name.' '.($patient->doctor->lname ?? '') : 'Unknown';

                        return "Patient #{$patient->id} (Doctor: {$doctorName})";
                    })
                    ->searchable()
                    ->required()
                    ->label('Patient'),
                Select::make('question_id')->relationship('question', 'question')->searchable()->preload()->required(),
                Textarea::make('answer')->required()->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['patient.doctor', 'question']))
            ->columns([
                TextColumn::make('id')->badge()->color('gray')->sortable(),
                TextColumn::make('patient_id')
                    ->label('Patient')
                    ->formatStateUsing(function ($record) {
                        if (! $record->patient) {
                            return "Patient #{$record->patient_id}";
                        }
                        $doctorName = $record->patient->doctor ? $record->patient->doctor->name.' '.($record->patient->doctor->lname ?? '') : 'Unknown';

                        return "Patient #{$record->patient_id} (Doctor: {$doctorName})";
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->where('patient_id', 'like', "%{$search}%")
                            ->orWhereHas('patient.doctor', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                    ->orWhere('lname', 'like', "%{$search}%");
                            });
                    })
                    ->sortable(),
                TextColumn::make('question.question')->limit(40)->searchable(),
                TextColumn::make('answer')->limit(50)->wrap(),
                TextColumn::make('created_at')->dateTime()->since()->sortable(),
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
                SelectFilter::make('section_id')
                    ->label('Section')
                    ->options(fn (): array => SectionsInfo::query()->pluck('section_name', 'id')->toArray())
                    ->searchable()
                    ->placeholder('All sections'),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No answers yet')
            ->emptyStateDescription('Patient answers will appear here.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnswers::route('/'),
            'create' => CreateAnswers::route('/create'),
            'view' => ViewAnswers::route('/{record}'),
            'edit' => EditAnswers::route('/{record}/edit'),
        ];
    }
}
