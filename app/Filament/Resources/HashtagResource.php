<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HashtagResource\Pages;
use App\Models\Hashtag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class HashtagResource extends Resource
{
    protected static ?string $model = Hashtag::class;

    protected static ?string $navigationIcon = 'heroicon-o-hashtag';

    protected static ?string $navigationLabel = 'Hashtags';

    protected static ?string $navigationGroup = '📱 Social Feed';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('hashtags_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Hashtag Information')->schema([                Forms\Components\TextInput::make('tag')->required()->maxLength(255),
                Forms\Components\TextInput::make('usage_count')->numeric()->default(0)
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->badge()->color('gray')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tag')->searchable()->sortable()->limit(50),
                Tables\Columns\TextColumn::make('usage_count')->searchable()->sortable()->limit(50),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->dateTime()->sortable()->since()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->after(fn () => Cache::forget('hashtags_count')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->after(fn () => Cache::forget('hashtags_count')),
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHashtags::route('/'),
            'create' => Pages\CreateHashtag::route('/create'),
            'view' => Pages\ViewHashtag::route('/{record}'),
            'edit' => Pages\EditHashtag::route('/{record}/edit'),
        ];
    }
}