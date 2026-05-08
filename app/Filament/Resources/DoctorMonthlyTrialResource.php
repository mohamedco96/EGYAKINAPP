<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorMonthlyTrialResource\Pages\CreateDoctorMonthlyTrial;
use App\Filament\Resources\DoctorMonthlyTrialResource\Pages\EditDoctorMonthlyTrial;
use App\Filament\Resources\DoctorMonthlyTrialResource\Pages\ListDoctorMonthlyTrials;
use App\Filament\Resources\DoctorMonthlyTrialResource\Pages\ViewDoctorMonthlyTrial;
use App\Modules\Chat\Models\DoctorMonthlyTrial;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class DoctorMonthlyTrialResource extends Resource
{
    protected static ?string $model = DoctorMonthlyTrial::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Monthly AI Trials';

    protected static string|\UnitEnum|null $navigationGroup = '💬 AI & Consultations';

    protected static ?int $navigationSort = 6;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('doctor_monthly_trials_count', 300, fn () => static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('AI Trial Information')
                ->description('Monthly AI consultation trial tracking')
                ->schema([
                    Select::make('doctor_id')
                        ->relationship('doctor', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Doctor')
                        ->helperText('Select the doctor for trial tracking'),

                    TextInput::make('trial_count')
                        ->numeric()
                        ->default(3)
                        ->required()
                        ->label('Trial Count')
                        ->helperText('Number of AI trials remaining this month'),

                    DatePicker::make('reset_date')
                        ->required()
                        ->label('Reset Date')
                        ->default(now()->addMonth()->startOfMonth())
                        ->helperText('Date when trials will reset (usually next month)'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['doctor']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable(['users.name', 'users.lname'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name.' '.($record->doctor->lname ?? '') : 'N/A')
                    ->description(fn ($record) => $record->doctor?->email)
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('trial_count')
                    ->label('Trials Remaining')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 1 => 'warning',
                        default => 'success',
                    })
                    ->icon('heroicon-o-cpu-chip')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('reset_date')
                    ->label('Reset Date')
                    ->date()
                    ->sortable()
                    ->description(fn ($record) => $record->reset_date ? 'Resets '.Carbon::parse($record->reset_date)->diffForHumans() : null)
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->getStateUsing(fn ($record) => Carbon::parse($record->reset_date)->isFuture())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('reset_date', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('no_trials_remaining')
                    ->label('No Trials Remaining')
                    ->toggle()
                    ->query(fn ($query) => $query->where('trial_count', '<=', 0)),

                Filter::make('expired')
                    ->label('Expired (Past Reset Date)')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('reset_date', '<', now())),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
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
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->after(function () {
                        Cache::forget('doctor_monthly_trials_count');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('doctor_monthly_trials_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->emptyStateHeading('No trial records yet')
            ->emptyStateDescription('Doctor monthly AI trial records will appear here.')
            ->emptyStateIcon('heroicon-o-calendar');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDoctorMonthlyTrials::route('/'),
            'create' => CreateDoctorMonthlyTrial::route('/create'),
            'view' => ViewDoctorMonthlyTrial::route('/{record}'),
            'edit' => EditDoctorMonthlyTrial::route('/{record}/edit'),
        ];
    }
}
