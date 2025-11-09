<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedPostResource\Pages;
use App\Models\FeedPost;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class FeedPostResource extends Resource
{
    protected static ?string $model = FeedPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Feed Posts';

    protected static ?string $navigationGroup = '📱 Social Feed';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('feed_posts_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Post Information')
                    ->description('Basic post details and content')
                    ->schema([
                        Forms\Components\Select::make('doctor_id')
                            ->relationship('doctor', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Doctor')
                            ->getSearchResultsUsing(fn (string $search) => \App\Models\User::where(function($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('lname', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            })->limit(50)->get()->pluck('full_name_with_email', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => \App\Models\User::find($value)?->full_name_with_email)
                            ->helperText('Select the doctor who created this post'),

                        Forms\Components\Select::make('group_id')
                            ->relationship('group', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->label('Group')
                            ->helperText('Optional: Select a group for this post'),

                        Forms\Components\Select::make('visibility')
                            ->options([
                                'public' => 'Public',
                                'private' => 'Private',
                                'group' => 'Group Only',
                            ])
                            ->default('public')
                            ->required()
                            ->native(false)
                            ->label('Visibility'),

                        Forms\Components\RichEditor::make('content')
                            ->required()
                            ->label('Post Content')
                            ->columnSpanFull()
                            ->maxLength(65535)
                            ->helperText('Write the post content. Use @ to mention users and # for hashtags.'),
                    ])->columns(3),

                Section::make('Media Attachments')
                    ->description('Images, videos, or other media files')
                    ->schema([
                        Forms\Components\Select::make('media_type')
                            ->options([
                                'image' => 'Image',
                                'video' => 'Video',
                                'mixed' => 'Mixed',
                                'none' => 'None',
                            ])
                            ->default('none')
                            ->reactive()
                            ->label('Media Type')
                            ->native(false),

                        FileUpload::make('media_path')
                            ->label('Media Files')
                            ->directory('feed_posts')
                            ->multiple()
                            ->maxFiles(5)
                            ->image()
                            ->imageEditor()
                            ->previewable(true)
                            ->reorderable()
                            ->appendFiles()
                            ->helperText('Upload up to 5 images or videos')
                            ->visible(fn ($get) => $get('media_type') !== 'none')
                            ->columnSpanFull(),
                    ])->columns(2)->collapsible(),

                Section::make('Statistics')
                    ->description('Post engagement metrics (read-only)')
                    ->schema([
                        Forms\Components\Placeholder::make('likes_count')
                            ->label('Likes Count')
                            ->content(fn ($record) => $record ? $record->likes()->count() : 0),

                        Forms\Components\Placeholder::make('comments_count')
                            ->label('Comments Count')
                            ->content(fn ($record) => $record ? $record->comments()->count() : 0),
                    ])->columns(2)->collapsible()->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable(['users.name', 'users.lname', 'users.email'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name . ' ' . $record->doctor->lname : 'N/A')
                    ->description(fn ($record) => $record->doctor?->email)
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('content')
                    ->label('Content')
                    ->searchable()
                    ->limit(60)
                    ->wrap()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 60) {
                            return $state;
                        }
                        return null;
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('visibility')
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

                Tables\Columns\TextColumn::make('media_type')
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

                Tables\Columns\TextColumn::make('group.name')
                    ->label('Group')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-user-group')
                    ->placeholder('No Group')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('likes.count')
                    ->label('Likes')
                    ->counts('likes')
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-o-heart')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('comments.count')
                    ->label('Comments')
                    ->counts('comments')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('saves.count')
                    ->label('Saves')
                    ->counts('saves')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-bookmark')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('hashtags')
                    ->label('Hashtags')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($record) => $record->hashtags->pluck('name')->map(fn($tag) => '#' . $tag)->join(', '))
                    ->placeholder('No hashtags')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('visibility')
                    ->label('Visibility')
                    ->options([
                        'public' => 'Public',
                        'private' => 'Private',
                        'group' => 'Group Only',
                    ])
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('media_type')
                    ->label('Media Type')
                    ->options([
                        'image' => 'Image',
                        'video' => 'Video',
                        'mixed' => 'Mixed',
                        'none' => 'None',
                    ])
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelUsing(fn ($value): ?string => \App\Models\User::find($value)?->full_name),

                Tables\Filters\SelectFilter::make('group_id')
                    ->label('Group')
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('has_media')
                    ->label('Has Media')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('media_path')),

                Tables\Filters\Filter::make('popular')
                    ->label('Popular Posts (10+ likes)')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->has('likes', '>=', 10)),

                Tables\Filters\Filter::make('created_at')
                    ->form([
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
                            $indicators[] = 'From ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Toggle columns'),
            )
            ->persistFiltersInSession()
            ->deselectAllRecordsWhenFiltered(true)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Feed Post Details')
                    ->modalWidth('5xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Cache::forget('feed_posts_count');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('changeVisibility')
                        ->label('Change Visibility')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('visibility')
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
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('feed_posts_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListFeedPosts::route('/'),
            'create' => Pages\CreateFeedPost::route('/create'),
            'view' => Pages\ViewFeedPost::route('/{record}'),
            'edit' => Pages\EditFeedPost::route('/{record}/edit'),
        ];
    }
}
