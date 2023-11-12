<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExaminationResource\Pages;
use App\Models\Examination;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ExaminationResource extends Resource
{
    protected static ?string $model = Examination::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Laboratory and radiology results';

    protected static ?string $navigationGroup = 'Patient Sections';

    protected static ?int $navigationSort = 9;

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
                Forms\Components\TextInput::make('current_creatinine')->label('Current creatinine'),
                Forms\Components\TextInput::make('basal_creatinine')->label('Basal creatinine, if available'),
                Forms\Components\select::make('renal_US')->label('Renal US')
                    ->options([
                        'Normal' => 'Normal',
                        'Abnormal' => 'Abnormal',
                    ]),
                Forms\Components\TextInput::make('specify_renal-US')->label('If renal US is abnormal, specify'),
                Forms\Components\TextInput::make('other'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable(),
                Tables\Columns\TextColumn::make('doctor.name')->label('Doctor Name')->searchable(),
                Tables\Columns\TextColumn::make('patient.name')->label('Patient Name')->searchable(),
                Tables\Columns\TextColumn::make('current_creatinine')->label('Current creatinine'),
                Tables\Columns\TextColumn::make('basal_creatinine')->label('Basal creatinine, if available'),
                Tables\Columns\TextColumn::make('renal_US')->label('Renal US'),
                Tables\Columns\TextColumn::make('specify_renal-US')->label('If renal US is abnormal, specify'),
                Tables\Columns\TextColumn::make('other'),
                Tables\Columns\TextColumn::make('created_at'),
                Tables\Columns\TextColumn::make('updated_at'),
            ])
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
            ])->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'), )
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
            'index' => Pages\ListExaminations::route('/'),
            'create' => Pages\CreateExamination::route('/create'),
            'edit' => Pages\EditExamination::route('/{record}/edit'),
        ];
    }
}
