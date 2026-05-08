<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserAchievementResource\Pages\CreateUserAchievement;
use App\Filament\Resources\UserAchievementResource\Pages\EditUserAchievement;
use App\Filament\Resources\UserAchievementResource\Pages\ListUserAchievements;
use App\Filament\Resources\UserAchievementResource\Pages\ViewUserAchievement;
use App\Models\UserAchievement;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class UserAchievementResource extends Resource
{
    protected static ?string $model = UserAchievement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'User Achievements';

    protected static string|\UnitEnum|null $navigationGroup = '⚙️ Administration';

    protected static ?int $navigationSort = 6;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('user_achievements_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('UserAchievement Information')->schema([Select::make('user_id')->relationship('user', 'name')->searchable()->preload()->required(),
                Select::make('achievement_id')->relationship('achievement', 'name')->searchable()->preload()->required(),
                Toggle::make('achieved')->default(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'achievement']))
            ->columns([
                TextColumn::make('id')->label('ID')->badge()->color('gray')->searchable()->sortable(),
                TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                TextColumn::make('achievement.name')->label('Achievement')->searchable()->sortable(),
                IconColumn::make('achieved')->boolean()->sortable(),
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
                DeleteAction::make()->after(fn () => Cache::forget('user_achievements_count')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->after(fn () => Cache::forget('user_achievements_count')),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No achievements recorded')
            ->emptyStateDescription('User achievements will appear here.')
            ->emptyStateIcon('heroicon-o-trophy');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserAchievements::route('/'),
            'create' => CreateUserAchievement::route('/create'),
            'view' => ViewUserAchievement::route('/{record}'),
            'edit' => EditUserAchievement::route('/{record}/edit'),
        ];
    }
}
