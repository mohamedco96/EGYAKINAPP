<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HashtagResource\Pages\CreateHashtag;
use App\Filament\Resources\HashtagResource\Pages\EditHashtag;
use App\Filament\Resources\HashtagResource\Pages\ListHashtags;
use App\Filament\Resources\HashtagResource\Pages\ViewHashtag;
use App\Models\Hashtag;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class HashtagResource extends Resource
{
    protected static ?string $model = Hashtag::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-hashtag';

    protected static ?string $navigationLabel = 'Hashtags';

    protected static string|\UnitEnum|null $navigationGroup = '📱 Community';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('hashtags_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Hashtag Information')->schema([TextInput::make('tag')->required()->maxLength(255),
                TextInput::make('usage_count')->numeric()->default(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->badge()->color('gray')->searchable()->sortable(),
                TextColumn::make('tag')->searchable()->sortable()->limit(50),
                TextColumn::make('usage_count')->searchable()->sortable()->limit(50),
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
                DeleteAction::make()->after(fn () => Cache::forget('hashtags_count')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->after(fn () => Cache::forget('hashtags_count')),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No hashtags yet')
            ->emptyStateDescription('Hashtags will appear here.')
            ->emptyStateIcon('heroicon-o-hashtag');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHashtags::route('/'),
            'create' => CreateHashtag::route('/create'),
            'view' => ViewHashtag::route('/{record}'),
            'edit' => EditHashtag::route('/{record}/edit'),
        ];
    }
}
