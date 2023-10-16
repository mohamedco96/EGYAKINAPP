<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RiskResource\Pages;
use App\Filament\Resources\RiskResource\RelationManagers;
use App\Models\Risk;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class RiskResource extends Resource
{
    protected static ?string $model = Risk::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Risk factors for AKI';
    protected static ?string $navigationGroup = 'Patients';
    protected static ?int $navigationSort = 7;
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('doctor_id')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Doctor Name'),
                Forms\Components\Select::make('patient_id')
                    ->relationship('patient', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Patient Name'),
                Forms\Components\Select::make('CKD_history')->label('History of CKD?')
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ]),
                Forms\Components\Select::make('AK_history')->label('Past history of AKI?')
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ]),
                Forms\Components\Select::make('cardiac-failure_history')->label('History of cardiac failure? ')
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ]),
                Forms\Components\Select::make('LCF_history')->label('History of LCF?')
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ]),
                Forms\Components\Select::make('neurological-impairment_disability_history')->label('History of neurological impairment or disability?')
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ]),
                Forms\Components\Select::make('sepsis_history')->label('History of sepsis?')
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ]),
                Forms\Components\Select::make('contrast_media')->label('Recent use of iodinated contrast media?')
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ]),
                Forms\Components\Select::make('drugs-with-potential-nephrotoxicity')->label('Current or recent use of drugs with potential nephrotoxicity?')
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ]),
                Forms\Components\Select::make('drug_name')->label('If the answer to the previous question is yes, what is the drug?')
                    ->options([
                        'NSAIDs' => 'NSAIDs',
                        'ACEi/ARBs' => 'ACEi/ARBs',
                        'Aminoglycosides' => 'Aminoglycosides',
                        'Diuretics' => 'Diuretics',
                        'Drug addiction' => 'Drug addiction',
                        'Others' => 'Others',
                    ]),
                Forms\Components\Select::make('hypovolemia_history')->label('History of hypovolemia?')
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ]),
                Forms\Components\Select::make('malignancy_history')->label('History of malignancy?')
                    ->options([
                        'Yes' => 'Yes',
                        'Maybe' => 'Maybe',
                    ]),
                Forms\Components\Select::make('trauma_history')->label('History of trauma?')
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ]),
                Forms\Components\Select::make('autoimmune-disease_history')->label('History of autoimmune disease?')
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ]),
                Forms\Components\TextInput::make('other-risk-factors')->label('Other risk factors?'),
                Forms\Components\RichEditor::make('other'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable(),
                Tables\Columns\TextColumn::make('owner.name')->label('Doctor Name')->searchable(),
                Tables\Columns\TextColumn::make('patient.name')->label('Patient Name')->searchable(),
                Tables\Columns\TextColumn::make('CKD_history')->label('History of CKD?'),
                Tables\Columns\TextColumn::make('AK_history')->label('Past history of AKI?'),
                Tables\Columns\TextColumn::make('cardiac-failure_history')->label('History of cardiac failure? '),
                Tables\Columns\TextColumn::make('LCF_history')->label('History of LCF?'),
                Tables\Columns\TextColumn::make('neurological-impairment_disability_history')->label('History of neurological impairment or disability?'),
                Tables\Columns\TextColumn::make('sepsis_history')->label('History of sepsis?'),
                Tables\Columns\TextColumn::make('contrast_media')->label('Recent use of iodinated contrast media?'),
                Tables\Columns\TextColumn::make('drugs-with-potential-nephrotoxicity')->label('Current or recent use of drugs with potential nephrotoxicity?'),
                Tables\Columns\TextColumn::make('drug_name')->label('If the answer to the previous question is yes, what is the drug?'),
                Tables\Columns\TextColumn::make('hypovolemia_history')->label('History of hypovolemia?'),
                Tables\Columns\TextColumn::make('malignancy_history')->label('History of malignancy?'),
                Tables\Columns\TextColumn::make('trauma_history')->label('History of trauma?'),
                Tables\Columns\TextColumn::make('autoimmune-disease_history')->label('History of autoimmune disease?'),
                Tables\Columns\TextColumn::make('other-risk-factors')->label('Other risk factors?'),
                Tables\Columns\TextColumn::make('other'),
                Tables\Columns\TextColumn::make('created_at')->label('Created At'),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated At'),
            ])
            ->filters([
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
                    })
            ])->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
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
            'index' => Pages\ListRisks::route('/'),
            'create' => Pages\CreateRisk::route('/create'),
            'edit' => Pages\EditRisk::route('/{record}/edit'),
        ];
    }
}
