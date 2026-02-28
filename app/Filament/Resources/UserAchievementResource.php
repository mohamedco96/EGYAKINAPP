<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserAchievementResource\Pages;
use App\Models\UserAchievement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class UserAchievementResource extends Resource
{
    protected static ?string $model = UserAchievement::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'User Achievements';

    protected static ?string $navigationGroup = 'App Data';

    protected static ?int $navigationSort = 6;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('user_achievements_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('UserAchievement Information')->schema([                Forms\Components\Select::make('user_id')->relationship('user', 'name')->searchable()->preload()->required(),
                Forms\Components\Select::make('achievement_id')->relationship('achievement', 'name')->searchable()->preload()->required(),
                Forms\Components\Toggle::make('achieved')->default(false)
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'achievement']))
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->badge()->color('gray')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('achievement.name')->label('Achievement')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('achieved')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->dateTime()->sortable()->since()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->after(fn () => Cache::forget('user_achievements_count')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->after(fn () => Cache::forget('user_achievements_count')),
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserAchievements::route('/'),
            'create' => Pages\CreateUserAchievement::route('/create'),
            'view' => Pages\ViewUserAchievement::route('/{record}'),
            'edit' => Pages\EditUserAchievement::route('/{record}/edit'),
        ];
    }
}