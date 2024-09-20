<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AchievementResource\Pages;
use App\Filament\Resources\AchievementResource\RelationManagers;
use App\Models\Achievement;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AchievementResource extends Resource
{
    protected static ?string $model = Achievement::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Achievements';

    protected static ?string $navigationGroup = 'App Data';

    protected static ?int $navigationSort = 5;
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->label('Description'),
                Forms\Components\Select::make('type')
                    ->label('Achievement Type')
                    ->options([
                        'patient' => 'Patient',
                        'score' => 'Score',
                        'outcome' => 'Outcome',
                    ]),
                Forms\Components\TextInput::make('score')
                    ->label('Achievement Score')
                    ->required(),
                FileUpload::make('image')
                    ->label('Achievement Image')
                    ->directory('achievement_images')
                    ->image()
                    ->imageEditor()
                    ->previewable(true)
                    ->imageCropAspectRatio('1:1')
                    ->imagePreviewHeight('250')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->toggleable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->toggleable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Achievement Type')
                    ->toggleable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Achievement Score')
                    ->toggleable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Achievement Image')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->circular()
                    ->extraAttributes(['style' => 'cursor: pointer;'])
                    ->action(function ($record) {
                        // Action to open the image in a new tab
                        return redirect()->away(asset('storage/achievement_images/' . $record->image));
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->toggleable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->toggleable()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                ExportBulkAction::make(),
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
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
            'index' => Pages\ListAchievements::route('/'),
            'create' => Pages\CreateAchievement::route('/create'),
            'edit' => Pages\EditAchievement::route('/{record}/edit'),
        ];
    }
}
