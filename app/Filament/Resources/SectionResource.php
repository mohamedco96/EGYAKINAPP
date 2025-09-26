<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectionResource\Pages;
use App\Models\Section;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SectionResource extends Resource
{
    protected static ?string $model = Section::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Patient Sections';

    protected static ?string $navigationGroup = '🏥 Patient Management';

    protected static ?int $navigationSort = 90;

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

                Forms\Components\Section::make('Section Completion Status')
                    ->schema([
                        Forms\Components\Toggle::make('section_1')
                            ->label('Section 1 - Patient History'),

                        Forms\Components\Toggle::make('section_2')
                            ->label('Section 2 - Complaints'),

                        Forms\Components\Toggle::make('section_3')
                            ->label('Section 3 - Causes'),

                        Forms\Components\Toggle::make('section_4')
                            ->label('Section 4 - Risk Factors'),

                        Forms\Components\Toggle::make('section_5')
                            ->label('Section 5 - Assessment'),

                        Forms\Components\Toggle::make('section_6')
                            ->label('Section 6 - Examination'),

                        Forms\Components\Toggle::make('section_7')
                            ->label('Section 7 - Decision'),
                    ])->columns(2),

                Forms\Components\Section::make('Status Information')
                    ->schema([
                        Forms\Components\Toggle::make('submit_status')
                            ->label('Submit Status'),

                        Forms\Components\Toggle::make('outcome_status')
                            ->label('Outcome Status'),

                        Forms\Components\TextInput::make('doc_id')
                            ->label('Document ID')
                            ->numeric(),
                    ])->columns(3),
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

                Tables\Columns\IconColumn::make('section_1')
                    ->label('S1')
                    ->boolean(),

                Tables\Columns\IconColumn::make('section_2')
                    ->label('S2')
                    ->boolean(),

                Tables\Columns\IconColumn::make('section_3')
                    ->label('S3')
                    ->boolean(),

                Tables\Columns\IconColumn::make('section_4')
                    ->label('S4')
                    ->boolean(),

                Tables\Columns\IconColumn::make('section_5')
                    ->label('S5')
                    ->boolean(),

                Tables\Columns\IconColumn::make('section_6')
                    ->label('S6')
                    ->boolean(),

                Tables\Columns\IconColumn::make('section_7')
                    ->label('S7')
                    ->boolean(),

                Tables\Columns\IconColumn::make('submit_status')
                    ->label('Submitted')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('submit_status'),
                Tables\Filters\TernaryFilter::make('outcome_status'),
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
            'index' => Pages\ListSections::route('/'),
            'create' => Pages\CreateSection::route('/create'),
            'edit' => Pages\EditSection::route('/{record}/edit'),
        ];
    }
}
