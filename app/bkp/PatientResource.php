<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientResource\Pages;
use App\Models\PatientHistory;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PatientResource extends Resource
{
    protected static ?string $model = PatientHistory::class;

    protected static ?string $navigationIcon = 'heroicon-s-users';

    protected static ?string $navigationLabel = 'Patient History';

    protected static ?string $navigationGroup = 'Patient Sections';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255)->label('Patient Name in Arabic'),
                Forms\Components\TextInput::make('hospital')->required()->maxLength(255)->label('Hospital Name in Arabic'),
                Forms\Components\Select::make('doctor_id')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Doctor Name')
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')->required()->label('First Name'),
                        Forms\Components\TextInput::make('lname')->required()->label('Last Name'),
                        Forms\Components\TextInput::make('email')->required()->email()->label('Email address')->maxLength(255),
                        Forms\Components\TextInput::make('password')->required()->password(),
                        Forms\Components\TextInput::make('age'),
                        Forms\Components\TextInput::make('specialty')->required(),
                        Forms\Components\TextInput::make('workingplace')->required()->label('Working place'),
                        Forms\Components\TextInput::make('phone')->required()->tel(),
                        Forms\Components\TextInput::make('job')->required(),
                        Forms\Components\TextInput::make('highestdegree')->required()->label('Highest degree'),
                    ])
                    ->required(),
                Forms\Components\Select::make('collected_data_from')->label('Collected Data From')
                    ->options([
                        'Patient himself' => 'Patient himself',
                        'Relative' => 'Relative',
                    ])->required(),
                Forms\Components\TextInput::make('NID')->label('National ID'),
                Forms\Components\TextInput::make('phone')->tel(),
                Forms\Components\TextInput::make('email')->label('Email address')->email(),
                Forms\Components\TextInput::make('age')->required(),
                Forms\Components\Select::make('gender')
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                    ])->required(),
                Forms\Components\Select::make('occupation')
                    ->options([
                        'No job' => 'No job',
                        'Retired' => 'Retired',
                        'Sick leave' => 'Sick leave',
                        'Has a job' => 'Has a job',
                    ])->required(),
                Forms\Components\Select::make('residency')
                    ->options([
                        'Urban' => 'Urban',
                        'Rural' => 'Rural',
                    ])->required(),
                Forms\Components\TextInput::make('governorate'),
                Forms\Components\Select::make('marital_status')
                    ->options([
                        'Married' => 'Married',
                        'Unmarried' => 'Unmarried',
                    ])->required(),
                Forms\Components\Select::make('educational_level')
                    ->options([
                        'Non' => 'Non',
                        'Primary school' => 'Primary school',
                        'Secondary school' => 'Secondary school',
                        'College' => 'College',
                        'Post-graduate' => 'Post-graduate',
                    ])->required(),
                Forms\Components\Select::make('special_habits_of_the_patient')
                    ->label('Special Habits of The patient')
                    ->multiple()
                    ->options([
                        'NO' => 'NO',
                        'Cigarette smoker' => 'Cigarette smoker',
                        'Shisha smoker' => 'Shisha smoker',
                        'Drug addict' => 'Drug addict',
                        'Others' => 'Others',
                    ]),
                Forms\Components\TextInput::make('other_habits_of_the_patient'),
                Forms\Components\Select::make('DM')->label('DM')
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                        'Recently discovered' => 'Recently discovered',
                    ])->required(),
                Forms\Components\TextInput::make('DM_duration')->label('DM Duration'),
                Forms\Components\Select::make('HTN')->label('HTN')
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                        'Recently discovered' => 'Recently discovered',
                    ])->required(),
                Forms\Components\TextInput::make('HTN_duration')->label('HTN Duration'),
                Forms\Components\TextInput::make('other'),
                Forms\Components\Radio::make('hidden')->boolean(),
                //Forms\Components\DatePicker::make('age')->required()->maxDate(now()),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                Tables\Columns\TextColumn::make('name')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                Tables\Columns\TextColumn::make('doctor.name')->toggleable(isToggledHiddenByDefault: false)->label('Doctor Name')->searchable(),
                Tables\Columns\TextColumn::make('hospital')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                Tables\Columns\TextColumn::make('collected_data_from')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('NID')->toggleable(isToggledHiddenByDefault: false)->label('National ID')->searchable(),
                Tables\Columns\TextColumn::make('phone')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                Tables\Columns\TextColumn::make('email')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                Tables\Columns\TextColumn::make('age')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('gender')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                Tables\Columns\TextColumn::make('occupation')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('residency')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('governorate')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('marital_status')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('educational_level')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('special_habits_of_the_patient')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('other_habits_of_the_patient')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('DM')->toggleable(isToggledHiddenByDefault: false)->label('DM'),
                Tables\Columns\TextColumn::make('DM_duration')->toggleable(isToggledHiddenByDefault: false)->label('DM Duration'),
                Tables\Columns\TextColumn::make('HTN')->toggleable(isToggledHiddenByDefault: false)->label('HTN'),
                Tables\Columns\TextColumn::make('HTN_duration')->toggleable(isToggledHiddenByDefault: false)->label('HTN Duration'),
                Tables\Columns\TextColumn::make('other')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\ToggleColumn::make('hidden')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('created_at')->toggleable(isToggledHiddenByDefault: false)->label('Created At'),
                Tables\Columns\TextColumn::make('updated_at')->toggleable(isToggledHiddenByDefault: false)->label('Updated At'),
            ])
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('Doctor Name')
                    ->relationship('doctor', 'name'),
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
            ])->toggleColumnsTriggerAction(
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
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
        ];
    }
}
