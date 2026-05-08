<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PollResource\Pages\CreatePoll;
use App\Filament\Resources\PollResource\Pages\EditPoll;
use App\Filament\Resources\PollResource\Pages\ListPolls;
use App\Filament\Resources\PollResource\Pages\ViewPoll;
use App\Models\Poll;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PollResource extends Resource
{
    protected static ?string $model = Poll::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Polls';

    protected static string|\UnitEnum|null $navigationGroup = '📱 Community';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('polls_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Poll Information')->schema([
                Select::make('feed_post_id')
                    ->relationship('feedPost', 'id')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Feed Post')
                    ->getOptionLabelUsing(fn ($value) => 'Post #'.$value)
                    ->helperText('Select the feed post for this poll'),
                TextInput::make('question')
                    ->required()
                    ->maxLength(255)
                    ->label('Poll Question')
                    ->columnSpanFull(),
                Toggle::make('allow_add_options')
                    ->label('Allow Users to Add Options')
                    ->default(false)
                    ->inline(false),
                Toggle::make('allow_multiple_choice')
                    ->label('Allow Multiple Choice')
                    ->default(false)
                    ->inline(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->badge()->color('gray')->searchable()->sortable(),
                TextColumn::make('feed_post_id')
                    ->label('Feed Post')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => 'Post #'.$state)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('question')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->weight('bold'),
                IconColumn::make('allow_add_options')
                    ->label('User Options')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),
                IconColumn::make('allow_multiple_choice')
                    ->label('Multiple Choice')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),
                TextColumn::make('options.count')
                    ->label('Options')
                    ->counts('options')
                    ->badge()
                    ->color('primary')
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
                DeleteAction::make()->after(fn () => Cache::forget('polls_count')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->after(fn () => Cache::forget('polls_count')),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No polls yet')
            ->emptyStateDescription('Feed post polls will appear here.')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPolls::route('/'),
            'create' => CreatePoll::route('/create'),
            'view' => ViewPoll::route('/{record}'),
            'edit' => EditPoll::route('/{record}/edit'),
        ];
    }
}
