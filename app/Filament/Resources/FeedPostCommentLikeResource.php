<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedPostCommentLikeResource\Pages\CreateFeedPostCommentLike;
use App\Filament\Resources\FeedPostCommentLikeResource\Pages\EditFeedPostCommentLike;
use App\Filament\Resources\FeedPostCommentLikeResource\Pages\ListFeedPostCommentLikes;
use App\Filament\Resources\FeedPostCommentLikeResource\Pages\ViewFeedPostCommentLike;
use App\Models\FeedPostCommentLike;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class FeedPostCommentLikeResource extends Resource
{
    protected static ?string $model = FeedPostCommentLike::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-hand-thumb-up';

    protected static ?string $navigationLabel = 'Comment Likes';

    protected static string|\UnitEnum|null $navigationGroup = '📱 Social Feed';

    protected static ?int $navigationSort = 7;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('feed_post_comment_likes_count', 300, fn () => static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Comment Like')->schema([
                Select::make('post_comment_id')->relationship('comment', 'id')->searchable()->preload()->required(),
                Select::make('doctor_id')->relationship('doctor', 'name')->searchable()->preload()->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['comment', 'doctor']))
            ->columns([
                TextColumn::make('id')->badge()->color('gray'),
                TextColumn::make('comment.id')->label('Comment')->sortable(),
                TextColumn::make('doctor.name')->searchable()->sortable(),
                TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->recordActions([ViewAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make(), ExportBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeedPostCommentLikes::route('/'),
            'create' => CreateFeedPostCommentLike::route('/create'),
            'view' => ViewFeedPostCommentLike::route('/{record}'),
            'edit' => EditFeedPostCommentLike::route('/{record}/edit'),
        ];
    }
}
