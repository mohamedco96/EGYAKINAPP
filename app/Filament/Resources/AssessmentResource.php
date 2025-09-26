<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssessmentResource\Pages;
use App\Models\Assessment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AssessmentResource extends Resource
{
    protected static ?string $model = Assessment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Assessments';

    protected static ?string $navigationGroup = '🏥 Patient Management';

    protected static ?int $navigationSort = 40;

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

                Forms\Components\Section::make('Vital Signs')
                    ->schema([
                        Forms\Components\TextInput::make('heart-rate/minute')
                            ->label('Heart Rate (per minute)')
                            ->numeric(),

                        Forms\Components\TextInput::make('respiratory-rate/minute')
                            ->label('Respiratory Rate (per minute)')
                            ->numeric(),

                        Forms\Components\TextInput::make('SBP')
                            ->label('Systolic Blood Pressure')
                            ->numeric(),

                        Forms\Components\TextInput::make('DBP')
                            ->label('Diastolic Blood Pressure')
                            ->numeric(),

                        Forms\Components\TextInput::make('GCS')
                            ->label('Glasgow Coma Scale')
                            ->numeric(),

                        Forms\Components\TextInput::make('oxygen_saturation')
                            ->label('Oxygen Saturation')
                            ->numeric(),

                        Forms\Components\TextInput::make('temperature')
                            ->label('Temperature')
                            ->numeric(),

                        Forms\Components\TextInput::make('UOP')
                            ->label('Urine Output'),

                        Forms\Components\TextInput::make('AVPU')
                            ->label('AVPU Scale'),
                    ])->columns(2),

                Forms\Components\Section::make('Physical Examination')
                    ->schema([
                        Forms\Components\TagsInput::make('skin_examination')
                            ->label('Skin Examination'),

                        Forms\Components\Textarea::make('skin_examination_clarify')
                            ->label('Skin Examination Details'),

                        Forms\Components\TagsInput::make('eye_examination')
                            ->label('Eye Examination'),

                        Forms\Components\Textarea::make('eye_examination_clarify')
                            ->label('Eye Examination Details'),

                        Forms\Components\TagsInput::make('ear_examination')
                            ->label('Ear Examination'),

                        Forms\Components\Textarea::make('ear_examination_clarify')
                            ->label('Ear Examination Details'),

                        Forms\Components\TagsInput::make('cardiac_examination')
                            ->label('Cardiac Examination'),

                        Forms\Components\Textarea::make('cardiac_examination_clarify')
                            ->label('Cardiac Examination Details'),

                        Forms\Components\TextInput::make('internal_jugular_vein')
                            ->label('Internal Jugular Vein'),

                        Forms\Components\TagsInput::make('chest_examination')
                            ->label('Chest Examination'),

                        Forms\Components\Textarea::make('chest_examination_clarify')
                            ->label('Chest Examination Details'),

                        Forms\Components\TagsInput::make('abdominal_examination')
                            ->label('Abdominal Examination'),

                        Forms\Components\Textarea::make('abdominal_examination_clarify')
                            ->label('Abdominal Examination Details'),

                        Forms\Components\Textarea::make('other')
                            ->label('Other Findings'),
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

                Tables\Columns\TextColumn::make('SBP')
                    ->label('SBP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('DBP')
                    ->label('DBP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('GCS')
                    ->label('GCS')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => Pages\ListAssessments::route('/'),
            'create' => Pages\CreateAssessment::route('/create'),
            'edit' => Pages\EditAssessment::route('/{record}/edit'),
        ];
    }
}
