<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedPostResource\Pages\CreateFeedPost;
use App\Filament\Resources\FeedPostResource\Pages\EditFeedPost;
use App\Filament\Resources\FeedPostResource\Pages\ListFeedPosts;
use App\Filament\Resources\FeedPostResource\Pages\ViewFeedPost;
use App\Models\FeedPost;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class FeedPostResource extends Resource
{
    protected static ?string $model = FeedPost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Feed Posts';

    protected static string|\UnitEnum|null $navigationGroup = '📱 Community';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('feed_posts_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Post Information')
                    ->description('Basic post details and content')
                    ->schema([
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
                            ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->full_name_with_email)
                            ->helperText('Select the doctor who created this post'),

                        Select::make('group_id')
                            ->relationship('group', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->label('Group')
                            ->helperText('Optional: Select a group for this post'),

                        Select::make('visibility')
                            ->options([
                                'public' => 'Public',
                                'private' => 'Private',
                                'group' => 'Group Only',
                            ])
                            ->default('public')
                            ->required()
                            ->native(false)
                            ->label('Visibility'),

                        RichEditor::make('content')
                            ->required()
                            ->label('Post Content')
                            ->columnSpanFull()
                            ->maxLength(65535)
                            ->helperText('Write the post content. Use @ to mention users and # for hashtags.'),
                    ])->columns(3),

                Section::make('Media Attachments')
                    ->description('Images, videos, or other media files')
                    ->schema([
                        Select::make('media_type')
                            ->options([
                                'image' => 'Image',
                                'video' => 'Video',
                                'mixed' => 'Mixed',
                                'none' => 'None',
                            ])
                            ->default('none')
                            ->label('Media Type')
                            ->native(false),

                        FileUpload::make('media_path')
                            ->label('Media Files')
                            ->directory('feed_posts')
                            ->visibility('public')
                            ->multiple()
                            ->maxFiles(5)
                            ->image()
                            ->imageEditor()
                            ->previewable(true)
                            ->reorderable()
                            ->appendFiles()
                            ->helperText('Upload up to 5 images or videos')
                            ->visibleJs('$get(\'media_type\') !== \'none\'')
                            ->columnSpanFull(),
                    ])->columns(2)->collapsible(),

                Section::make('Statistics')
                    ->description('Post engagement metrics (read-only)')
                    ->schema([
                        Placeholder::make('likes_count')
                            ->label('Likes Count')
                            ->content(fn ($record) => $record ? $record->likes()->count() : 0),

                        Placeholder::make('comments_count')
                            ->label('Comments Count')
                            ->content(fn ($record) => $record ? $record->comments()->count() : 0),
                    ])->columns(2)->collapsible()->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['doctor', 'group', 'hashtags']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable(['users.name', 'users.lname', 'users.email'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name.' '.$record->doctor->lname : 'N/A')
                    ->description(fn ($record) => $record->doctor?->email)
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('content')
                    ->label('Content')
                    ->searchable()
                    ->limit(60)
                    ->wrap()
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 60) {
                            return $state;
                        }

                        return null;
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('visibility')
                    ->label('Visibility')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'public' => 'success',
                        'group' => 'info',
                        'private' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'public' => 'heroicon-o-globe-alt',
                        'group' => 'heroicon-o-user-group',
                        'private' => 'heroicon-o-lock-closed',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('media_type')
                    ->label('Media')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'image' => 'success',
                        'video' => 'info',
                        'mixed' => 'warning',
                        'none' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'image' => 'heroicon-o-photo',
                        'video' => 'heroicon-o-film',
                        'mixed' => 'heroicon-o-document',
                        'none' => 'heroicon-o-minus-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('group.name')
                    ->label('Group')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-user-group')
                    ->placeholder('No Group')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('likes.count')
                    ->label('Likes')
                    ->counts('likes')
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-o-heart')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('comments.count')
                    ->label('Comments')
                    ->counts('comments')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('saves.count')
                    ->label('Saves')
                    ->counts('saves')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-bookmark')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('hashtags')
                    ->label('Hashtags')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($record) => $record->hashtags->pluck('name')->map(fn ($tag) => '#'.$tag)->join(', '))
                    ->placeholder('No hashtags')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                SelectFilter::make('visibility')
                    ->label('Visibility')
                    ->options([
                        'public' => 'Public',
                        'private' => 'Private',
                        'group' => 'Group Only',
                    ])
                    ->multiple()
                    ->searchable(),

                SelectFilter::make('media_type')
                    ->label('Media Type')
                    ->options([
                        'image' => 'Image',
                        'video' => 'Video',
                        'mixed' => 'Mixed',
                        'none' => 'None',
                    ])
                    ->multiple()
                    ->searchable(),

                SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->full_name),

                SelectFilter::make('group_id')
                    ->label('Group')
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('has_media')
                    ->label('Has Media')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('media_path')),

                Filter::make('popular')
                    ->label('Popular Posts (10+ likes)')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->has('likes', '>=', 10)),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('From'),
                        DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'From '.Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until '.Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Toggle columns'),
            )
            ->persistFiltersInSession()
            ->deferFilters(false)
            ->deselectAllRecordsWhenFiltered(true)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Feed Post Details')
                    ->modalWidth('5xl'),
                EditAction::make(),
                DeleteAction::make()
                    ->after(function () {
                        Cache::forget('feed_posts_count');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('changeVisibility')
                        ->label('Change Visibility')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->form([
                            Select::make('visibility')
                                ->label('New Visibility')
                                ->options([
                                    'public' => 'Public',
                                    'private' => 'Private',
                                    'group' => 'Group Only',
                                ])
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each->update(['visibility' => $data['visibility']]);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Visibility updated for selected posts'),
                    DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('feed_posts_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->emptyStateHeading('No feed posts yet')
            ->emptyStateDescription('Feed posts from doctors will appear here.')
            ->emptyStateIcon('heroicon-o-newspaper');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeedPosts::route('/'),
            'create' => CreateFeedPost::route('/create'),
            'view' => ViewFeedPost::route('/{record}'),
            'edit' => EditFeedPost::route('/{record}/edit'),
        ];
    }
}
