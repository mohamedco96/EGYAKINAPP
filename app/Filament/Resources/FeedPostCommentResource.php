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
            Forms\Components\Section::make('FeedPostComment Information')->schema([                Forms\Components\Select::make('feed_post_id')->relationship('feed_post', 'name')->searchable()->preload()->required(),
                Forms\Components\Select::make('doctor_id')->relationship('doctor', 'name')->searchable()->preload()->required(),
                Forms\Components\Textarea::make('comment')->required()->rows(3),
                Forms\Components\Select::make('parent_id')->relationship('parent', 'name')->searchable()->preload()->required()
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->badge()->color('gray')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('feed_post.name')->label('Feed_post')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('doctor.name')->label('Doctor')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('comment')->searchable()->sortable()->limit(50),
                Tables\Columns\TextColumn::make('parent.name')->label('Parent')->searchable()->sortable(),
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