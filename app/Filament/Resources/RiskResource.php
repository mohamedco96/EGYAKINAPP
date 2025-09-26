<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RiskResource\Pages;
use App\Models\Risk;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class RiskResource extends Resource
{
    protected static ?string $model = Risk::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Risk Factors';

    protected static ?string $navigationGroup = '🏥 Patient Management';

    protected static ?int $navigationSort = 30;

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
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('patient_id')
                    ->relationship('patient', 'id')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Section::make('Medical History')
                    ->schema([
                        Forms\Components\Toggle::make('CKD_history')
                            ->label('CKD History'),

                        Forms\Components\Toggle::make('AK_history')
                            ->label('AK History'),

                        Forms\Components\Toggle::make('cardiac-failure_history')
                            ->label('Cardiac Failure History'),

                        Forms\Components\Toggle::make('LCF_history')
                            ->label('LCF History'),

                        Forms\Components\Toggle::make('neurological-impairment_disability_history')
                            ->label('Neurological Impairment/Disability History'),

                        Forms\Components\Toggle::make('sepsis_history')
                            ->label('Sepsis History'),

                        Forms\Components\Toggle::make('hypovolemia_history')
                            ->label('Hypovolemia History'),

                        Forms\Components\Toggle::make('malignancy_history')
                            ->label('Malignancy History'),

                        Forms\Components\Toggle::make('trauma_history')
                            ->label('Trauma History'),

                        Forms\Components\Toggle::make('autoimmune-disease_history')
                            ->label('Autoimmune Disease History'),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Risk Factors')
                    ->schema([
                        Forms\Components\Toggle::make('contrast_media')
                            ->label('Contrast Media'),

                        Forms\Components\Toggle::make('drugs-with-potential-nephrotoxicity')
                            ->label('Drugs with Potential Nephrotoxicity'),

                        Forms\Components\TextInput::make('drug_name')
                            ->label('Drug Name'),

                        Forms\Components\Textarea::make('other-risk-factors')
                            ->label('Other Risk Factors'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('patient.id')
                    ->label('Patient ID')
                    ->sortable(),

                Tables\Columns\IconColumn::make('CKD_history')
                    ->label('CKD')
                    ->boolean(),

                Tables\Columns\IconColumn::make('sepsis_history')
                    ->label('Sepsis')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('CKD_history'),
                Tables\Filters\TernaryFilter::make('sepsis_history'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
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
