<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedPostCommentLikeResource\Pages;
use App\Models\FeedPostCommentLike;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class FeedPostCommentLikeResource extends Resource
{
    protected static ?string $model = FeedPostCommentLike::class;
    protected static ?string $navigationIcon = 'heroicon-o-hand-thumb-up';
    protected static ?string $navigationLabel = 'Comment Likes';
    protected static ?string $navigationGroup = '📱 Social Feed';
    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('feed_post_comment_likes_count', 300, fn() => static::getModel()::count());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Comment Like')->schema([
                Forms\Components\Select::make('post_comment_id')->relationship('comment', 'id')->searchable()->preload()->required(),
                Forms\Components\Select::make('doctor_id')->relationship('doctor', 'name')->searchable()->preload()->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('comment.id')->label('Comment')->sortable(),
                Tables\Columns\TextColumn::make('doctor.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make(), ExportBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeedPostCommentLikes::route('/'),
            'create' => Pages\CreateFeedPostCommentLike::route('/create'),
            'view' => Pages\ViewFeedPostCommentLike::route('/{record}'),
            'edit' => Pages\EditFeedPostCommentLike::route('/{record}/edit'),
        ];
    }
}
