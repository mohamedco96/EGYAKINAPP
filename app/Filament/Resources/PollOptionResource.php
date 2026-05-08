<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PollOptionResource\Pages\CreatePollOption;
use App\Filament\Resources\PollOptionResource\Pages\EditPollOption;
use App\Filament\Resources\PollOptionResource\Pages\ListPollOptions;
use App\Filament\Resources\PollOptionResource\Pages\ViewPollOption;
use App\Models\PollOption;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PollOptionResource extends Resource
{
    protected static ?string $model = PollOption::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'Poll Options';

    protected static string|\UnitEnum|null $navigationGroup = '📱 Social Feed';

    protected static ?int $navigationSort = 9;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('poll_options_count', 300, fn () => static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Poll Option')->schema([
                Select::make('poll_id')
                    ->relationship('poll', 'question')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Poll Question')
                    ->helperText('Select the poll for this option'),
                TextInput::make('option_text')
                    ->required()
                    ->maxLength(255)
                    ->label('Option Text')
                    ->helperText('The text for this poll option')
                    ->columnSpanFull(),
                Placeholder::make('votes_info')
                    ->label('Vote Count')
                    ->content(fn ($record) => $record ? $record->votes()->count().' votes' : '0 votes (read-only)')
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['poll']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('poll.question')
                    ->label('Poll Question')
                    ->limit(40)
                    ->searchable()
                    ->wrap()
                    ->weight('bold'),
                TextColumn::make('option_text')
                    ->label('Option')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('votes.count')
                    ->label('Votes')
                    ->counts('votes')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-hand-thumb-up')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->after(fn () => Cache::forget('poll_options_count')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(fn () => Cache::forget('poll_options_count')),
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPollOptions::route('/'),
            'create' => CreatePollOption::route('/create'),
            'view' => ViewPollOption::route('/{record}'),
            'edit' => EditPollOption::route('/{record}/edit'),
        ];
    }
}
