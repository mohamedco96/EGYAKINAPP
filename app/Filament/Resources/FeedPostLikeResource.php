<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedPostLikeResource\Pages;
use App\Models\FeedPostLike;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class FeedPostLikeResource extends Resource
{
    protected static ?string $model = FeedPostLike::class;
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationLabel = 'Post Likes';
    protected static ?string $navigationGroup = '📱 Social Feed';
    protected static ?int $navigationSort = 6;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('feed_post_likes_count', 300, fn() => static::getModel()::count());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Like')->schema([
                Forms\Components\Select::make('feed_post_id')->relationship('post', 'id')->searchable()->preload()->required(),
                Forms\Components\Select::make('doctor_id')->relationship('doctor', 'name')->searchable()->preload()->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
            'index' => Pages\ListFeedPostLikes::route('/'),
            'create' => Pages\CreateFeedPostLike::route('/create'),
            'view' => Pages\ViewFeedPostLike::route('/{record}'),
            'edit' => Pages\EditFeedPostLike::route('/{record}/edit'),
        ];
    }
}
