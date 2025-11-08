<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PollOptionResource\Pages;
use App\Models\PollOption;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PollOptionResource extends Resource
{
    protected static ?string $model = PollOption::class;
    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'Poll Options';
    protected static ?string $navigationGroup = '📱 Social Feed';
    protected static ?int $navigationSort = 9;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('poll_options_count', 300, fn() => static::getModel()::count());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Poll Option')->schema([
                Forms\Components\Select::make('poll_id')->relationship('poll', 'question')->searchable()->preload()->required(),
                Forms\Components\TextInput::make('option_text')->required()->maxLength(255),
                Forms\Components\TextInput::make('votes_count')->numeric()->default(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('poll.question')->limit(30)->searchable(),
                Tables\Columns\TextColumn::make('option_text')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('votes_count')->badge()->color('success')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make(), ExportBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPollOptions::route('/'),
            'create' => Pages\CreatePollOption::route('/create'),
            'view' => Pages\ViewPollOption::route('/{record}'),
            'edit' => Pages\EditPollOption::route('/{record}/edit'),
        ];
    }
}
