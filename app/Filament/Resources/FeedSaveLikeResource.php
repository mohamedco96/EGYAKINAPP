<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedSaveLikeResource\Pages\CreateFeedSaveLike;
use App\Filament\Resources\FeedSaveLikeResource\Pages\EditFeedSaveLike;
use App\Filament\Resources\FeedSaveLikeResource\Pages\ListFeedSaveLikes;
use App\Filament\Resources\FeedSaveLikeResource\Pages\ViewFeedSaveLike;
use App\Models\FeedSaveLike;
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

class FeedSaveLikeResource extends Resource
{
    protected static ?string $model = FeedSaveLike::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bookmark';

    protected static ?string $navigationLabel = 'Saved Posts';

    protected static string|\UnitEnum|null $navigationGroup = '📱 Social Feed';

    protected static ?int $navigationSort = 8;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('feed_save_likes_count', 300, fn () => static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Saved Post')->schema([
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
            'index' => ListFeedSaveLikes::route('/'),
            'create' => CreateFeedSaveLike::route('/create'),
            'view' => ViewFeedSaveLike::route('/{record}'),
            'edit' => EditFeedSaveLike::route('/{record}/edit'),
        ];
    }
}
