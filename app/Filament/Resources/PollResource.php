<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PollResource\Pages;
use App\Models\Poll;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PollResource extends Resource
{
    protected static ?string $model = Poll::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Polls';

    protected static ?string $navigationGroup = '📱 Social Feed';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('polls_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Poll Information')->schema([
                Forms\Components\Select::make('feed_post_id')
                    ->relationship('feedPost', 'id')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Feed Post')
                    ->getOptionLabelUsing(fn ($value) => 'Post #' . $value)
                    ->helperText('Select the feed post for this poll'),
                Forms\Components\TextInput::make('question')
                    ->required()
                    ->maxLength(255)
                    ->label('Poll Question')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('allow_add_options')
                    ->label('Allow Users to Add Options')
                    ->default(false)
                    ->inline(false),
                Forms\Components\Toggle::make('allow_multiple_choice')
                    ->label('Allow Multiple Choice')
                    ->default(false)
                    ->inline(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->badge()->color('gray')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('feed_post_id')
                    ->label('Feed Post')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => 'Post #' . $state)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('question')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->weight('bold'),
                Tables\Columns\IconColumn::make('allow_add_options')
                    ->label('User Options')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),
                Tables\Columns\IconColumn::make('allow_multiple_choice')
                    ->label('Multiple Choice')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('options.count')
                    ->label('Options')
                    ->counts('options')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->dateTime()->sortable()->since()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->after(fn () => Cache::forget('polls_count')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->after(fn () => Cache::forget('polls_count')),
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPolls::route('/'),
            'create' => Pages\CreatePoll::route('/create'),
            'view' => Pages\ViewPoll::route('/{record}'),
            'edit' => Pages\EditPoll::route('/{record}/edit'),
        ];
    }
}