<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionsResource\Pages;
use App\Filament\Resources\QuestionsResource\RelationManagers;
use App\Models\Questions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestionsResource extends Resource
{
    protected static ?string $model = Questions::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Questions';
    protected static ?string $navigationGroup = 'Patients';
    protected static ?int $navigationSort = 6;
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('section_id')
                    ->label('Section ID')
                    ->options([
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                        '5' => '5',
                        '6' => '6',
                        '7' => '7',
                        '8' => '8',
                        '9' => '9',
                    ])->required(),
                Forms\Components\Select::make('section_name')
                    ->label('Section Name')
                    ->options([
                        'Patient History' => 'Patient History',
                        'Complaint' => 'Complaint',
                        'Cause of AKI' => 'Cause of AKI',
                        'Risk factors for AKI' => 'Risk factors for AKI',
                        'Assessment of the patient' => 'Assessment of the patient',
                        'Medical examinations' => 'Medical examinations',
                        'Medical decision' => 'Medical decision',
                        'Outcome' => 'Outcome',
                        'Additional information' => 'Additional information',
                    ])->required(),
                Forms\Components\TextInput::make('question')->required(),
                Forms\Components\TextInput::make('values'),
                Forms\Components\Select::make('type')
                    ->options([
                        'string' => 'String',
                        'select' => 'Select',
                        'multiple' => 'Multiple Select',
                    ])->required(),
                Forms\Components\Radio::make('mandatory')
                    ->required()
                    ->boolean(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable(),
                Tables\Columns\TextColumn::make('section_id')->label('Section ID')->searchable(),
                Tables\Columns\TextColumn::make('section_name')->label('Section Name')->searchable(),
                Tables\Columns\TextColumn::make('question'),
                Tables\Columns\TextColumn::make('values'),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('mandatory'),
                Tables\Columns\TextColumn::make('created_at')->label('Created At'),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated At'),
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
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestions::route('/create'),
            'edit' => Pages\EditQuestions::route('/{record}/edit'),
        ];
    }
}
