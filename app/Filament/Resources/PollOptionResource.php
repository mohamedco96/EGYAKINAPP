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
                Forms\Components\Select::make('poll_id')
                    ->relationship('poll', 'question')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Poll Question')
                    ->helperText('Select the poll for this option'),
                Forms\Components\TextInput::make('option_text')
                    ->required()
                    ->maxLength(255)
                    ->label('Option Text')
                    ->helperText('The text for this poll option')
                    ->columnSpanFull(),
                Forms\Components\Placeholder::make('votes_info')
                    ->label('Vote Count')
                    ->content(fn ($record) => $record ? $record->votes()->count() . ' votes' : '0 votes (read-only)')
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('poll.question')
                    ->label('Poll Question')
                    ->limit(40)
                    ->searchable()
                    ->wrap()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('option_text')
                    ->label('Option')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\TextColumn::make('votes.count')
                    ->label('Votes')
                    ->counts('votes')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-hand-thumb-up')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(fn () => Cache::forget('poll_options_count')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(fn () => Cache::forget('poll_options_count')),
                    ExportBulkAction::make(),
                ]),
            ]);
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
