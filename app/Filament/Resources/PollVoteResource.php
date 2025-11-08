<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PollVoteResource\Pages;
use App\Models\PollVote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PollVoteResource extends Resource
{
    protected static ?string $model = PollVote::class;
    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $navigationLabel = 'Poll Votes';
    protected static ?string $navigationGroup = '📱 Social Feed';
    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('poll_votes_count', 300, fn() => static::getModel()::count());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Vote')->schema([
                Forms\Components\Select::make('poll_option_id')->relationship('option', 'option_text')->searchable()->preload()->required(),
                Forms\Components\Select::make('doctor_id')->relationship('doctor', 'name')->searchable()->preload()->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('option.option_text')->limit(30)->searchable(),
                Tables\Columns\TextColumn::make('doctor.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make(), ExportBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPollVotes::route('/'),
            'create' => Pages\CreatePollVote::route('/create'),
            'view' => Pages\ViewPollVote::route('/{record}'),
            'edit' => Pages\EditPollVote::route('/{record}/edit'),
        ];
    }
}
