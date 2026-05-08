<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedPostCommentResource\Pages\CreateFeedPostComment;
use App\Filament\Resources\FeedPostCommentResource\Pages\EditFeedPostComment;
use App\Filament\Resources\FeedPostCommentResource\Pages\ListFeedPostComments;
use App\Filament\Resources\FeedPostCommentResource\Pages\ViewFeedPostComment;
use App\Models\FeedPostComment;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
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

class FeedPostCommentResource extends Resource
{
    protected static ?string $model = FeedPostComment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationLabel = 'Feed Comments';

    protected static string|\UnitEnum|null $navigationGroup = '📱 Community';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('feed_post_comments_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Comment Information')->schema([
                Select::make('feed_post_id')
                    ->relationship('feedPost', 'id')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Feed Post')
                    ->getOptionLabelUsing(fn ($value) => 'Post #'.$value),

                Select::make('doctor_id')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Doctor')
                    ->getSearchResultsUsing(fn (string $search) => User::where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })->limit(50)->get()->pluck('full_name_with_email', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->full_name_with_email),

                Textarea::make('comment')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull()
                    ->label('Comment Text'),

                Select::make('parent_id')
                    ->relationship('parent', 'id')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->label('Parent Comment (for replies)')
                    ->helperText('Leave empty for top-level comments')
                    ->getOptionLabelUsing(fn ($value) => 'Comment #'.$value),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['doctor']))
            ->columns([
                TextColumn::make('id')->label('ID')->badge()->color('gray')->searchable()->sortable(),
                TextColumn::make('feed_post_id')
                    ->label('Feed Post')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => 'Post #'.$state)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name.' '.($record->doctor->lname ?? '') : 'N/A')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('comment')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('parent_id')
                    ->label('Parent Comment')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => $state ? 'Comment #'.$state : null)
                    ->placeholder('Top Level')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')->label('Created')->dateTime()->sortable()->since()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->after(fn () => Cache::forget('feed_post_comments_count')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->after(fn () => Cache::forget('feed_post_comments_count')),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No feed comments')
            ->emptyStateDescription('User comments on feed posts will appear here.')
            ->emptyStateIcon('heroicon-o-chat-bubble-bottom-center-text');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeedPostComments::route('/'),
            'create' => CreateFeedPostComment::route('/create'),
            'view' => ViewFeedPostComment::route('/{record}'),
            'edit' => EditFeedPostComment::route('/{record}/edit'),
        ];
    }
}
