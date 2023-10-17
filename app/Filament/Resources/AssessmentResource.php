<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssessmentResource\Pages;
use App\Filament\Resources\AssessmentResource\RelationManagers;
use App\Models\Assessment;
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

class AssessmentResource extends Resource
{
    protected static ?string $model = Assessment::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Assessment of the patient';
    protected static ?string $navigationGroup = 'Patient Sections';
    protected static ?int $navigationSort = 8;
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
                Forms\Components\TextInput::make('heart-rate/minute')->label('Heart rate/minute'),
                Forms\Components\TextInput::make('respiratory-rate/minute')->label('Respiratory rate/minute'),
                Forms\Components\TextInput::make('SBP')->label('SBP'),
                Forms\Components\TextInput::make('DBP')->label('DBP'),
                Forms\Components\TextInput::make('GCS')->label('GCS'),
                Forms\Components\TextInput::make('oxygen_saturation')->label('Oxygen saturation (%)'),
                Forms\Components\TextInput::make('temperature')->label('Temperature'),
                Forms\Components\TextInput::make('UOP')->label('UOP (ml/hour) '),
                Forms\Components\select::make('AVPU')->label('AVPU')
                    ->options([
                        'Alert' => 'Alert',
                        'Verbal' => 'Verbal',
                        'Pain' => 'Pain',
                        'Unresponsive' => 'Unresponsive',
                    ]),
                Forms\Components\Select::make('skin_examination')->label('Skin examination')
                    ->multiple()
                    ->options([
                        'Normal' => 'Normal',
                        'Petechiae, purpura' => 'Petechiae, purpura',
                        'Ecchymosis' => 'Ecchymosis',
                        'Livido reticularis' => 'Livido reticularis',
                        'Digital ischemia' => 'Digital ischemia',
                        'Butterfly rash' => 'Butterfly rash',
                        'Palpable purpura' => 'Palpable purpura',
                        'Systemic vasculitis' => 'Systemic vasculitis',
                        'Track marks (ie, intravenous drug abuse)' => 'Track marks (ie, intravenous drug abuse)',
                        'Oedema' => 'Oedema',
                        'Signs of dehydration' => 'Signs of dehydration',
                        'Other' =>'Other',
                    ]),
                Forms\Components\TextInput::make('skin_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Forms\Components\Select::make('eye_examination')->label('Eye examination')
                    ->multiple()
                    ->options([
                        'Normal' => 'Normal',
                        'Jaundice' => 'Jaundice',
                        'Pallor' => 'Pallor',
                        'Uveitis' => 'Uveitis',
                        'Other' =>'Other',
                    ]),
                Forms\Components\TextInput::make('eye_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Forms\Components\select::make('ear_examination')->label('Ear examination')
                    ->options([
                        'Normal' => 'Normal',
                        'Hearing loss' => 'Hearing loss',
                        'Other' => 'Other',
                    ]),
                Forms\Components\TextInput::make('ear_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Forms\Components\select::make('cardiac_examination')->label('Cardiac examination')
                    ->multiple()
                    ->options([
                        'Normal' => 'Normal',
                        'Pericardial rub' => 'Pericardial rub',
                        'Murmur' => 'Murmur',
                        'Abnormal heart sounds' => 'Abnormal heart sounds',
                        'Irregular rhythm' =>'Irregular rhythm',
                        'Other' =>'Other',
                    ]),
                Forms\Components\TextInput::make('cardiac_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Forms\Components\select::make('internal_jugular_vein')->label('Internal jugular vein')
                    ->options([
                        'Congested' => 'Congested',
                        'Non-congested' => 'Non-congested',
                    ]),
                Forms\Components\select::make('chest_examination')->label('Chest examination')
                    ->multiple()
                    ->options([
                        'Normal' => 'Normal',
                        'Coarse crepitations' => 'Coarse crepitations',
                        'Fine crepitations' => 'Fine crepitations',
                        'Pleural rub' => 'Pleural rub',
                        'Reduction of air entry' =>'Reduction of air entry',
                        'Other' =>'Other',
                    ]),
                Forms\Components\TextInput::make('chest_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Forms\Components\select::make('abdominal_examination')->label('Abdominal examination')
                    ->multiple()
                    ->options([
                        'Loin Pain' => 'Loin Pain',
                        'Ascites' => 'Ascites',
                        'Palpable UB' => 'Palpable UB',
                        'Palpable Kidney' => 'Palpable Kidney',
                        'Bruit' =>'Bruit',
                        'Other' =>'Other',
                    ]),
                Forms\Components\TextInput::make('abdominal_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Forms\Components\TextInput::make('other')->label('Other important findings in examination'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable(),
                Tables\Columns\TextColumn::make('doctor.name')->label('Doctor Name')->searchable(),
                Tables\Columns\TextColumn::make('patient.name')->label('Patient Name')->searchable(),
                Tables\Columns\TextColumn::make('heart-rate/minute')->label('Heart rate/minute'),
                Tables\Columns\TextColumn::make('respiratory-rate/minute')->label('Respiratory rate/minute'),
                Tables\Columns\TextColumn::make('SBP')->label('SBP'),
                Tables\Columns\TextColumn::make('DBP')->label('DBP'),
                Tables\Columns\TextColumn::make('GCS')->label('GCS'),
                Tables\Columns\TextColumn::make('oxygen_saturation')->label('Oxygen saturation (%)'),
                Tables\Columns\TextColumn::make('temperature')->label('Temperature'),
                Tables\Columns\TextColumn::make('UOP')->label('UOP (ml/hour) '),
                Tables\Columns\TextColumn::make('AVPU')->label('AVPU'),
                Tables\Columns\TextColumn::make('skin_examination')->label('Skin examination'),
                Tables\Columns\TextColumn::make('skin_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Tables\Columns\TextColumn::make('eye_examination')->label('Eye examination'),
                Tables\Columns\TextColumn::make('eye_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Tables\Columns\TextColumn::make('ear_examination')->label('Ear examination'),
                Tables\Columns\TextColumn::make('ear_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Tables\Columns\TextColumn::make('cardiac_examination')->label('Cardiac examination'),
                Tables\Columns\TextColumn::make('cardiac_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Tables\Columns\TextColumn::make('internal_jugular_vein')->label('Internal jugular vein'),
                Tables\Columns\TextColumn::make('chest_examination')->label('Chest examination'),
                Tables\Columns\TextColumn::make('chest_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Tables\Columns\TextColumn::make('abdominal_examination')->label('Abdominal examination'),
                Tables\Columns\TextColumn::make('abdominal_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Tables\Columns\TextColumn::make('other')->label('Other important findings in examination'),
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
            'index' => Pages\ListAssessments::route('/'),
            'create' => Pages\CreateAssessment::route('/create'),
            'edit' => Pages\EditAssessment::route('/{record}/edit'),
        ];
    }
}
