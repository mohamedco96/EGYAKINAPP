<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutcomeResource\Pages;
use App\Models\Outcome;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class OutcomeResource extends Resource
{
    protected static ?string $model = Outcome::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Outcome';

    protected static ?string $navigationGroup = 'Patient Sections';

    protected static ?int $navigationSort = 11;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('doctor_id')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Doctor Name'),
                Forms\Components\Select::make('patient_id')
                    ->relationship('patient', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Patient Name'),
                Forms\Components\select::make('outcome_of_the_patient')->label('Outcome of the patient')
                    ->options([
                        'Survivor' => 'Survivor',
                        'Death' => 'Death',
                    ]),
                Forms\Components\TextInput::make('creatinine_on_discharge')->label('Creatinine on discharge'),
                Forms\Components\TextInput::make('duration_of_admission')->label('Duration of Admission'),
                Forms\Components\select::make('final_status')->label('Final status')
                    ->options([
                        'No improvement (not on dialysis)' => 'No improvement (not on dialysis)',
                        'No improvement (on dialysis)' => 'No improvement (on dialysis)',
                        'Partial improvement' => 'Partial improvement',
                        'Complete improvement (90% improvement of serum creatinine)' => 'Complete improvement (90% improvement of serum creatinine)',
                    ]),
                Forms\Components\TextInput::make('other'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                Tables\Columns\TextColumn::make('doctor.name')->toggleable(isToggledHiddenByDefault: false)->label('Doctor Name')->searchable(),
                Tables\Columns\TextColumn::make('patient.name')->toggleable(isToggledHiddenByDefault: false)->label('Patient Name')->searchable(),
                Tables\Columns\TextColumn::make('outcome_of_the_patient')->toggleable(isToggledHiddenByDefault: false)->label('Outcome of the patient'),
                Tables\Columns\TextColumn::make('creatinine_on_discharge')->toggleable(isToggledHiddenByDefault: false)->label('Creatinine on discharge'),
                Tables\Columns\TextColumn::make('duration_of_admission')->toggleable(isToggledHiddenByDefault: false)->label('Duration of Admission'),
                Tables\Columns\TextColumn::make('final_status')->toggleable(isToggledHiddenByDefault: false)->label('Final status'),
                Tables\Columns\TextColumn::make('other')->toggleable(isToggledHiddenByDefault: false)->label('Other Outcome'),
                Tables\Columns\TextColumn::make('created_at')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')->toggleable(isToggledHiddenByDefault: false),
            ])
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('Doctor Name')
                    ->relationship('doctor', 'name'),
                Tables\Filters\SelectFilter::make('Patient Name')
                    ->relationship('patient', 'name'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
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
            'index' => Pages\ListOutcomes::route('/'),
            'create' => Pages\CreateOutcome::route('/create'),
            'edit' => Pages\EditOutcome::route('/{record}/edit'),
        ];
    }
}
