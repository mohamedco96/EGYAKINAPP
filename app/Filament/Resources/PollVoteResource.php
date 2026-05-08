<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PollVoteResource\Pages\CreatePollVote;
use App\Filament\Resources\PollVoteResource\Pages\EditPollVote;
use App\Filament\Resources\PollVoteResource\Pages\ListPollVotes;
use App\Filament\Resources\PollVoteResource\Pages\ViewPollVote;
use App\Models\PollVote;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PollVoteResource extends Resource
{
    protected static ?string $model = PollVote::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Poll Votes';

    protected static string|\UnitEnum|null $navigationGroup = '📱 Social Feed';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('poll_votes_count', 300, fn () => static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vote')->schema([
                Select::make('poll_option_id')->relationship('option', 'option_text')->searchable()->preload()->required(),
                Select::make('doctor_id')->relationship('doctor', 'name')->searchable()->preload()->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['option', 'doctor']))
            ->columns([
                TextColumn::make('id')->badge()->color('gray'),
                TextColumn::make('option.option_text')->limit(30)->searchable(),
                TextColumn::make('doctor.name')->searchable()->sortable(),
                TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->recordActions([ViewAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make(), ExportBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPollVotes::route('/'),
            'create' => CreatePollVote::route('/create'),
            'view' => ViewPollVote::route('/{record}'),
            'edit' => EditPollVote::route('/{record}/edit'),
        ];
    }
}
