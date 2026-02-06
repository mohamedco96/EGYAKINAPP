<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedSaveLikeResource\Pages;
use App\Models\FeedSaveLike;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class FeedSaveLikeResource extends Resource
{
    protected static ?string $model = FeedSaveLike::class;
    protected static ?string $navigationIcon = 'heroicon-o-bookmark';
    protected static ?string $navigationLabel = 'Saved Posts';
    protected static ?string $navigationGroup = '📱 Social Feed';
    protected static ?int $navigationSort = 8;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('feed_save_likes_count', 300, fn() => static::getModel()::count());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Saved Post')->schema([
                Forms\Components\Select::make('feed_post_id')->relationship('post', 'id')->searchable()->preload()->required(),
                Forms\Components\Select::make('doctor_id')->relationship('doctor', 'name')->searchable()->preload()->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['post', 'doctor']))
            ->columns([
                Tables\Columns\TextColumn::make('id')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('post.id')->label('Post')->sortable(),
                Tables\Columns\TextColumn::make('doctor.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make(), ExportBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeedSaveLikes::route('/'),
            'create' => Pages\CreateFeedSaveLike::route('/create'),
            'view' => Pages\ViewFeedSaveLike::route('/{record}'),
            'edit' => Pages\EditFeedSaveLike::route('/{record}/edit'),
        ];
    }
}
