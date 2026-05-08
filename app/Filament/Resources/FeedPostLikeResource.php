<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedPostLikeResource\Pages\CreateFeedPostLike;
use App\Filament\Resources\FeedPostLikeResource\Pages\EditFeedPostLike;
use App\Filament\Resources\FeedPostLikeResource\Pages\ListFeedPostLikes;
use App\Filament\Resources\FeedPostLikeResource\Pages\ViewFeedPostLike;
use App\Models\FeedPostLike;
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

class FeedPostLikeResource extends Resource
{
    protected static ?string $model = FeedPostLike::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Post Likes';

    protected static string|\UnitEnum|null $navigationGroup = '📱 Social Feed';

    protected static ?int $navigationSort = 6;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('feed_post_likes_count', 300, fn () => static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Like')->schema([
                Select::make('feed_post_id')->relationship('post', 'id')->searchable()->preload()->required(),
                Select::make('doctor_id')->relationship('doctor', 'name')->searchable()->preload()->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['post', 'doctor']))
            ->columns([
                TextColumn::make('id')->badge()->color('gray'),
                TextColumn::make('post.id')->label('Post')->sortable(),
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
            'index' => ListFeedPostLikes::route('/'),
            'create' => CreateFeedPostLike::route('/create'),
            'view' => ViewFeedPostLike::route('/{record}'),
            'edit' => EditFeedPostLike::route('/{record}/edit'),
        ];
    }
}
