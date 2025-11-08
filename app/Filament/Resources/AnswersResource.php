<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnswersResource\Pages;
use App\Models\Answers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AnswersResource extends Resource
{
    protected static ?string $model = Answers::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Patient Answers';
    protected static ?string $navigationGroup = '📊 Medical Data';
    protected static ?int $navigationSort = 8;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('answers_count', 300, fn() => static::getModel()::count());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Answer Information')->schema([
                Forms\Components\Select::make('patient_id')->relationship('patient', 'name')->searchable()->preload()->required(),
                Forms\Components\Select::make('question_id')->relationship('question', 'question')->searchable()->preload()->required(),
                Forms\Components\Textarea::make('answer')->required()->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->badge()->color('gray')->sortable(),
                Tables\Columns\TextColumn::make('patient.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('question.question')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('answer')->limit(50)->wrap(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->sortable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make(), ExportBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnswers::route('/'),
            'create' => Pages\CreateAnswers::route('/create'),
            'view' => Pages\ViewAnswers::route('/{record}'),
            'edit' => Pages\EditAnswers::route('/{record}/edit'),
        ];
    }
}
