<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostCommentsResource\Pages\CreatePostComments;
use App\Filament\Resources\PostCommentsResource\Pages\EditPostComments;
use App\Filament\Resources\PostCommentsResource\Pages\ListPostComments;
use App\Filament\Resources\PostCommentsResource\Pages\ViewPostComments;
use App\Modules\Posts\Models\PostComments;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PostCommentsResource extends Resource
{
    protected static ?string $model = PostComments::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-oval-left-ellipsis';

    protected static ?string $navigationLabel = 'Post Comments';

    protected static string|\UnitEnum|null $navigationGroup = '📱 Community';

    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('post_comments_count', 300, fn () => static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Comment')->schema([
                Select::make('post_id')->relationship('post', 'title')->searchable()->preload()->required(),
                Select::make('doctor_id')->relationship('doctor', 'name')->searchable()->preload()->required(),
                Textarea::make('comment')->required()->rows(4)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['post:id,title', 'doctor']))
            ->columns([
                TextColumn::make('id')->badge()->color('gray'),
                TextColumn::make('post.title')->limit(30)->searchable(),
                TextColumn::make('doctor.name')->searchable()->sortable(),
                TextColumn::make('comment')->limit(50)->wrap(),
                TextColumn::make('created_at')->dateTime()->since()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make(), ExportBulkAction::make()])])
            ->emptyStateHeading('No post comments')
            ->emptyStateDescription('Comments on posts will appear here.')
            ->emptyStateIcon('heroicon-o-chat-bubble-oval-left-ellipsis');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPostComments::route('/'),
            'create' => CreatePostComments::route('/create'),
            'view' => ViewPostComments::route('/{record}'),
            'edit' => EditPostComments::route('/{record}/edit'),
        ];
    }
}
