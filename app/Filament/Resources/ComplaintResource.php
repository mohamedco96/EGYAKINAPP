<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComplaintResource\Pages;
use App\Filament\Resources\ComplaintResource\RelationManagers;
use App\Models\Complaint;
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

class ComplaintResource extends Resource
{
    protected static ?string $model = Complaint::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Complaint';
    protected static ?string $navigationGroup = 'Patients';
    protected static ?int $navigationSort = 5;
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Doctor Name'),
                Forms\Components\Select::make('patient_id')
                    ->relationship('patient', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Patient Name'),
                Forms\Components\Select::make('where_was_th_patient_seen_for_the_first_time')
                    ->label('Where was the patient seen for the first time')
                    ->options([
                        'OPC' => 'OPC',
                        'ER' => 'ER',
                        'Admitted' => 'Admitted',
                    ]),
                Forms\Components\TextInput::make('place_of_admission'),
                Forms\Components\DateTimePicker::make('date_of_admission'),
                Forms\Components\Select::make('main_omplaint')
                    ->label('The main complaint (you can choose more than one answer)')
                    ->multiple()
                    ->options([
                        'Oliguria/Anuria' => 'Oliguria/Anuria',
                        'Change in color of urine' =>'Change in color of urine',
                        'Burning micturation' =>'Burning micturation',
                        'Puffiness of face/edema LL/Anasarca' =>'Puffiness of face/edema LL/Anasarca',
                        'Fatigue/tiredness' =>'Fatigue/tiredness',
                        'Confusion' =>'Confusion',
                        'Chest pain/pressure' =>'Chest pain/pressure',
                        'Shortness of beath' =>'Shortness of beath',
                        'Nausea/Vomiting' =>'Nausea/Vomiting',
                        'Seizures' =>'Seizures',
                        'Accidentally discovered' =>'Accidentally discovered',
                        'Other' =>'Other',
                    ]),
                Forms\Components\TextInput::make('other'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable(),
                Tables\Columns\TextColumn::make('owner.name')->label('Doctor Name')->searchable(),
                Tables\Columns\TextColumn::make('patient.name')->label('Patient Name')->searchable(),
                Tables\Columns\TextColumn::make('where_was_th_patient_seen_for_the_first_time')->searchable(),
                Tables\Columns\TextColumn::make('place_of_admission'),
                Tables\Columns\TextColumn::make('date_of_admission'),
                Tables\Columns\TextColumn::make('main_omplaint'),
                Tables\Columns\TextColumn::make('other'),
                Tables\Columns\TextColumn::make('created_at'),
                Tables\Columns\TextColumn::make('updated_at'),
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
            'index' => Pages\ListComplaints::route('/'),
            'create' => Pages\CreateComplaint::route('/create'),
            'edit' => Pages\EditComplaint::route('/{record}/edit'),
        ];
    }
}
