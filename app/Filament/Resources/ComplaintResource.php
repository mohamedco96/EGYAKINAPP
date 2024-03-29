<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComplaintResource\Pages;
use App\Models\Complaint;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ComplaintResource extends Resource
{
    protected static ?string $model = Complaint::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Complaint';

    protected static ?string $navigationGroup = 'Patient Sections';

    protected static ?int $navigationSort = 4;

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
                        'Change in color of urine' => 'Change in color of urine',
                        'Burning micturation' => 'Burning micturation',
                        'Puffiness of face/edema LL/Anasarca' => 'Puffiness of face/edema LL/Anasarca',
                        'Fatigue/tiredness' => 'Fatigue/tiredness',
                        'Confusion' => 'Confusion',
                        'Chest pain/pressure' => 'Chest pain/pressure',
                        'Shortness of beath' => 'Shortness of beath',
                        'Nausea/Vomiting' => 'Nausea/Vomiting',
                        'Seizures' => 'Seizures',
                        'Accidentally discovered' => 'Accidentally discovered',
                        'Other' => 'Other',
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
                Tables\Columns\TextColumn::make('where_was_th_patient_seen_for_the_first_time')->toggleable(isToggledHiddenByDefault: false)->label('Where was the patient seen for the first time?'),
                Tables\Columns\TextColumn::make('place_of_admission')->toggleable(isToggledHiddenByDefault: false)->label('If the patient is admitted, what is the place of admission?'),
                Tables\Columns\TextColumn::make('date_of_admission')->toggleable(isToggledHiddenByDefault: false)->label('Date of admission'),
                Tables\Columns\TextColumn::make('main_omplaint')->toggleable(isToggledHiddenByDefault: false)->label('The main complaint'),
                Tables\Columns\TextColumn::make('other')->toggleable(isToggledHiddenByDefault: false)->label('If the response to the previous question is other, What is the main complaint of patient?'),
                Tables\Columns\TextColumn::make('other')->toggleable(isToggledHiddenByDefault: false),
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
            'index' => Pages\ListComplaints::route('/'),
            'create' => Pages\CreateComplaint::route('/create'),
            'edit' => Pages\EditComplaint::route('/{record}/edit'),
        ];
    }
}
