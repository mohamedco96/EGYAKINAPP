<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectionResource\Pages;
use App\Filament\Resources\SectionResource\RelationManagers;
use App\Models\Section;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SectionResource extends Resource
{
    protected static ?string $model = Section::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Sections';
    protected static ?string $navigationGroup = 'Patients';
    protected static ?int $navigationSort = 4;
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
                Forms\Components\TextInput::make('patient_id')->label('Patient ID'),
                Forms\Components\Radio::make('section_1')
                    ->label('Section 1 status')
                    ->boolean(),
                Forms\Components\Radio::make('section_2')
                    ->label('Section 2 status')
                    ->boolean(),
                Forms\Components\Radio::make('section_3')
                    ->label('Section 3 status')
                    ->boolean(),
                Forms\Components\Radio::make('section_4')
                    ->label('Section 4 status')
                    ->boolean(),
                Forms\Components\Radio::make('section_5')
                    ->label('Section 5 status')
                    ->boolean(),
                Forms\Components\Radio::make('section_6')
                    ->label('Section 6 status')
                    ->boolean(),
                Forms\Components\Radio::make('section_7')
                    ->label('Section 7 status')
                    ->boolean(),
                Forms\Components\Radio::make('submit_status')
                    ->label('Submit status')
                    ->boolean(),
                Forms\Components\Radio::make('outcome_status')
                    ->label('Outcome status')
                    ->boolean(),
                Forms\Components\Select::make('doc_id')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Doctor that do Outcome'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable(),
                Tables\Columns\TextColumn::make('owner.name')->label('Doctor Name')->searchable(),
                Tables\Columns\TextColumn::make('patient_id')->searchable(),
                Tables\Columns\TextColumn::make('section_1'),
                Tables\Columns\TextColumn::make('section_2'),
                Tables\Columns\TextColumn::make('section_3'),
                Tables\Columns\TextColumn::make('section_4'),
                Tables\Columns\TextColumn::make('section_5'),
                Tables\Columns\TextColumn::make('section_6'),
                Tables\Columns\TextColumn::make('section_7'),
                Tables\Columns\TextColumn::make('submit_status'),
                Tables\Columns\TextColumn::make('outcome_status'),
                Tables\Columns\TextColumn::make('doc_id'),
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
            'index' => Pages\ListSections::route('/'),
            'create' => Pages\CreateSection::route('/create'),
            'edit' => Pages\EditSection::route('/{record}/edit'),
        ];
    }
}
