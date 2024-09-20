<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientStatusesResource\Pages;
use App\Filament\Resources\PatientStatusesResource\RelationManagers;
use App\Models\PatientStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PatientStatusesResource extends Resource
{
    protected static ?string $model = PatientStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Patients';

    protected static ?string $navigationLabel = 'Patient Status';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                Tables\Columns\TextColumn::make('doctor.name')->toggleable(isToggledHiddenByDefault: false)->label('Doctor Name')->searchable(),
                Tables\Columns\TextColumn::make('patient_id')->toggleable(isToggledHiddenByDefault: false)->label('Patient ID')->searchable(),
                Tables\Columns\TextColumn::make('key')->label('key')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\ToggleColumn::make('status')->toggleable(isToggledHiddenByDefault: false)->label('status'),
                Tables\Columns\TextColumn::make('created_at')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPatientStatuses::route('/'),
            'create' => Pages\CreatePatientStatuses::route('/create'),
            'edit' => Pages\EditPatientStatuses::route('/{record}/edit'),
        ];
    }
}
