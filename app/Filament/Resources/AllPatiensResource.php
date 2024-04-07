<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AllPatiensResource\Pages;
use App\Models\PatientHistory;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAllAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AllPatiensResource extends Resource
{
    protected static ?string $model = PatientHistory::class;

    protected static ?string $navigationIcon = 'heroicon-s-users';

    protected static ?string $navigationLabel = 'Patients';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //PatientHistory Section
                Tables\Columns\TextColumn::make('id')->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('doctor.name')->label('Doctor Name')->searchable(),
                Tables\Columns\TextColumn::make('hospital')->searchable(),
                Tables\Columns\TextColumn::make('collected_data_from'),
                Tables\Columns\TextColumn::make('NID')->label('National ID')->searchable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('age'),
                Tables\Columns\TextColumn::make('gender')->searchable(),
                Tables\Columns\TextColumn::make('occupation'),
                Tables\Columns\TextColumn::make('residency'),
                Tables\Columns\TextColumn::make('governorate'),
                Tables\Columns\TextColumn::make('marital_status'),
                Tables\Columns\TextColumn::make('educational_level'),
                Tables\Columns\TextColumn::make('special_habits_of_the_patient')->label('Special habits of the patient?'),
                Tables\Columns\TextColumn::make('other_habits_of_the_patient')->label('Others habits of the patient'),
                Tables\Columns\TextColumn::make('DM')->label('DM')->label('Does the patient have DM?'),
                Tables\Columns\TextColumn::make('DM_duration')->label('DM Duration')->label('If the patient has DM, what is the duration in years?'),
                Tables\Columns\TextColumn::make('HTN')->label('HTN')->label('Does the patient have HTN?'),
                Tables\Columns\TextColumn::make('HTN_duration')->label('HTN Duration')->label('If the patient has HTN, what is the duration in years?'),
                Tables\Columns\TextColumn::make('other'),
                //Complaint Section
                Tables\Columns\TextColumn::make('complaint.where_was_th_patient_seen_for_the_first_time')->label('Where was the patient seen for the first time?'),
                Tables\Columns\TextColumn::make('complaint.place_of_admission')->label('If the patient is admitted, what is the place of admission?'),
                Tables\Columns\TextColumn::make('complaint.date_of_admission')->label('Date of admission'),
                Tables\Columns\TextColumn::make('complaint.main_omplaint')->label('The main complaint'),
                Tables\Columns\TextColumn::make('complaint.other')->label('If the response to the previous question is other, What is the main complaint of patient?'),
                //Cause of AKI Section
                Tables\Columns\TextColumn::make('cause.cause_of_AKI')->label('Cause of AKI'),
                Tables\Columns\TextColumn::make('cause.pre-renal_causes')->label('Pre-renal causes of AKI in this patient include'),
                Tables\Columns\TextColumn::make('cause.pre-renal_others')->label('If the cause of pre-renal AKI is others, what is the cause?'),
                Tables\Columns\TextColumn::make('cause.renal_causes')->label('Intrinsic renal causes of AKI in this patient include'),
                Tables\Columns\TextColumn::make('cause.renal_others')->label('If the cause of intrinsic renal AKI is others, what is the cause?'),
                Tables\Columns\TextColumn::make('cause.post-renal_causes')->label('Post-renal causes of AKI in this patient include'),
                Tables\Columns\TextColumn::make('cause.post-renal_others')->label('If the cause of post-renal AKI is others, what is the cause?'),
                Tables\Columns\TextColumn::make('cause.other')->label('Other Causes'),
                //Risk factors for AKI Section
                Tables\Columns\TextColumn::make('risk.CKD_history')->label('History of CKD?'),
                Tables\Columns\TextColumn::make('risk.AK_history')->label('Past history of AKI?'),
                Tables\Columns\TextColumn::make('risk.cardiac-failure_history')->label('History of cardiac failure? '),
                Tables\Columns\TextColumn::make('risk.LCF_history')->label('History of LCF?'),
                Tables\Columns\TextColumn::make('risk.neurological-impairment_disability_history')->label('History of neurological impairment or disability?'),
                Tables\Columns\TextColumn::make('risk.sepsis_history')->label('History of sepsis?'),
                Tables\Columns\TextColumn::make('risk.contrast_media')->label('Recent use of iodinated contrast media?'),
                Tables\Columns\TextColumn::make('risk.drugs-with-potential-nephrotoxicity')->label('Current or recent use of drugs with potential nephrotoxicity?'),
                Tables\Columns\TextColumn::make('risk.drug_name')->label('If the answer to the previous question is yes, what is the drug?'),
                Tables\Columns\TextColumn::make('risk.hypovolemia_history')->label('History of hypovolemia?'),
                Tables\Columns\TextColumn::make('risk.malignancy_history')->label('History of malignancy?'),
                Tables\Columns\TextColumn::make('risk.trauma_history')->label('History of trauma?'),
                Tables\Columns\TextColumn::make('risk.autoimmune-disease_history')->label('History of autoimmune disease?'),
                Tables\Columns\TextColumn::make('risk.other-risk-factors')->label('Other risk factors?'),
                //Assessment of the patient Section
                Tables\Columns\TextColumn::make('assessment.heart-rate/minute')->label('Heart rate/minute'),
                Tables\Columns\TextColumn::make('assessment.respiratory-rate/minute')->label('Respiratory rate/minute'),
                Tables\Columns\TextColumn::make('assessment.SBP')->label('SBP'),
                Tables\Columns\TextColumn::make('assessment.DBP')->label('DBP'),
                Tables\Columns\TextColumn::make('assessment.GCS')->label('GCS'),
                Tables\Columns\TextColumn::make('assessment.oxygen_saturation')->label('Oxygen saturation (%)'),
                Tables\Columns\TextColumn::make('assessment.temperature')->label('Temperature'),
                Tables\Columns\TextColumn::make('assessment.UOP')->label('UOP (ml/hour) '),
                Tables\Columns\TextColumn::make('assessment.AVPU')->label('AVPU'),
                Tables\Columns\TextColumn::make('assessment.skin_examination')->label('Skin examination'),
                Tables\Columns\TextColumn::make('assessment.skin_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Tables\Columns\TextColumn::make('assessment.eye_examination')->label('Eye examination'),
                Tables\Columns\TextColumn::make('assessment.eye_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Tables\Columns\TextColumn::make('assessment.ear_examination')->label('Ear examination'),
                Tables\Columns\TextColumn::make('assessment.ear_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Tables\Columns\TextColumn::make('assessment.cardiac_examination')->label('Cardiac examination'),
                Tables\Columns\TextColumn::make('assessment.cardiac_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Tables\Columns\TextColumn::make('assessment.internal_jugular_vein')->label('Internal jugular vein'),
                Tables\Columns\TextColumn::make('assessment.chest_examination')->label('Chest examination'),
                Tables\Columns\TextColumn::make('assessment.chest_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Tables\Columns\TextColumn::make('assessment.abdominal_examination')->label('Abdominal examination'),
                Tables\Columns\TextColumn::make('assessment.abdominal_examination_clarify')->label('If the response to the previous question is others, clarify?'),
                Tables\Columns\TextColumn::make('assessment.other')->label('Other important findings in examination'),
                //Laboratory and radiology results Section
                Tables\Columns\TextColumn::make('examination.current_creatinine')->label('Current creatinine'),
                Tables\Columns\TextColumn::make('examination.basal_creatinine')->label('Basal creatinine, if available'),
                Tables\Columns\TextColumn::make('examination.renal_US')->label('Renal US'),
                Tables\Columns\TextColumn::make('examination.specify_renal-US')->label('If renal US is abnormal, specify'),
                Tables\Columns\TextColumn::make('examination.Other laboratory findings'),
                Tables\Columns\TextColumn::make('examination.Other radiology findings'),
                //Decision Section
                Tables\Columns\TextColumn::make('decision.medical_decision')->label('Medical Decision'),
                Tables\Columns\TextColumn::make('decision.other')->label('Other Decision'),
                //Outcome Section
                Tables\Columns\TextColumn::make('outcome.outcome_of_the_patient')->label('Outcome of the patient'),
                Tables\Columns\TextColumn::make('outcome.creatinine_on_discharge')->label('Creatinine on discharge'),
                Tables\Columns\TextColumn::make('outcome.duration_of_admission')->label('Duration of Admission'),
                Tables\Columns\TextColumn::make('outcome.final_status')->label('Final status'),
                Tables\Columns\TextColumn::make('outcome.other')->label('Other Outcome'),

                Tables\Columns\TextColumn::make('created_at')->label('Created At'),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated At'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('Doctor Name')
                    ->relationship('doctor', 'name'),
                Tables\Filters\SelectFilter::make('name'),
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
                /*Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'cat' => 'Cat',
                        'dog' => 'Dog',
                        'rabbit' => 'Rabbit',
                    ]),*/
            ])->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'), )
            ->actions([
                //Tables\Actions\EditAction::make(),
                //Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                    //ExportAllAction::make(), // New export all action
                    //ExportAction::make()
                ]),
            ])
            ->emptyStateActions([
                //Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListAllPatiens::route('/'),
            //'create' => Pages\CreateAllPatiens::route('/create'),
            //'edit' => Pages\EditAllPatiens::route('/{record}/edit'),
        ];
    }
}
