<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedPostCommentResource\Pages;
use App\Models\FeedPostComment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class FeedPostCommentResource extends Resource
{
    protected static ?string $model = FeedPostComment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationLabel = 'Feed Comments';

    protected static ?string $navigationGroup = '📱 Social Feed';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('feed_post_comments_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Comment Information')->schema([
                Forms\Components\Select::make('feed_post_id')
                    ->relationship('feedPost', 'id')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Feed Post')
                    ->getOptionLabelUsing(fn ($value) => 'Post #' . $value),

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
                    ->getOptionLabelUsing(fn ($value): ?string => \App\Models\User::find($value)?->full_name_with_email),

                Forms\Components\Textarea::make('comment')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull()
                    ->label('Comment Text'),

                Forms\Components\Select::make('parent_id')
                    ->relationship('parent', 'id')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->label('Parent Comment (for replies)')
                    ->helperText('Leave empty for top-level comments')
                    ->getOptionLabelUsing(fn ($value) => 'Comment #' . $value),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->badge()->color('gray')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('feed_post_id')
                    ->label('Feed Post')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => 'Post #' . $state)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name . ' ' . ($record->doctor->lname ?? '') : 'N/A')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('comment')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\TextColumn::make('parent_id')
                    ->label('Parent Comment')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => $state ? 'Comment #' . $state : null)
                    ->placeholder('Top Level')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->dateTime()->sortable()->since()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->after(fn () => Cache::forget('feed_post_comments_count')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->after(fn () => Cache::forget('feed_post_comments_count')),
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeedPostComments::route('/'),
            'create' => Pages\CreateFeedPostComment::route('/create'),
            'view' => Pages\ViewFeedPostComment::route('/{record}'),
            'edit' => Pages\EditFeedPostComment::route('/{record}/edit'),
        ];
    }
}