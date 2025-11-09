<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorMonthlyTrialResource\Pages;
use App\Modules\Chat\Models\DoctorMonthlyTrial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class DoctorMonthlyTrialResource extends Resource
{
    protected static ?string $model = DoctorMonthlyTrial::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Monthly AI Trials';
    protected static ?string $navigationGroup = '💬 AI & Consultations';
    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('doctor_monthly_trials_count', 300, fn() => static::getModel()::count());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('AI Trial Information')
                ->description('Monthly AI consultation trial tracking')
                ->schema([
                    Forms\Components\Select::make('doctor_id')
                        ->relationship('doctor', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Doctor')
                        ->helperText('Select the doctor for trial tracking'),

                    Forms\Components\TextInput::make('trial_count')
                        ->numeric()
                        ->default(3)
                        ->required()
                        ->label('Trial Count')
                        ->helperText('Number of AI trials remaining this month'),

                    Forms\Components\DatePicker::make('reset_date')
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
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable(['users.name', 'users.lname'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name . ' ' . ($record->doctor->lname ?? '') : 'N/A')
                    ->description(fn ($record) => $record->doctor?->email)
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('trial_count')
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

                Tables\Columns\TextColumn::make('reset_date')
                    ->label('Reset Date')
                    ->date()
                    ->sortable()
                    ->description(fn ($record) => $record->reset_date ? 'Resets ' . \Carbon\Carbon::parse($record->reset_date)->diffForHumans() : null)
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->getStateUsing(fn ($record) => \Carbon\Carbon::parse($record->reset_date)->isFuture())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('reset_date', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('no_trials_remaining')
                    ->label('No Trials Remaining')
                    ->toggle()
                    ->query(fn ($query) => $query->where('trial_count', '<=', 0)),

                Tables\Filters\Filter::make('expired')
                    ->label('Expired (Past Reset Date)')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('reset_date', '<', now())),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Cache::forget('doctor_monthly_trials_count');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('doctor_monthly_trials_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->emptyStateHeading('No trial records yet')
            ->emptyStateDescription('Doctor monthly AI trial records will appear here.')
            ->emptyStateIcon('heroicon-o-calendar');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDoctorMonthlyTrials::route('/'),
            'create' => Pages\CreateDoctorMonthlyTrial::route('/create'),
            'view' => Pages\ViewDoctorMonthlyTrial::route('/{record}'),
            'edit' => Pages\EditDoctorMonthlyTrial::route('/{record}/edit'),
        ];
    }
}
