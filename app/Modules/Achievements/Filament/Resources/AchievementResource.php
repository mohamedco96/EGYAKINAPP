<?php

namespace App\Modules\Achievements\Filament\Resources;

use App\Modules\Achievements\Filament\Resources\AchievementResource\Pages;
use App\Modules\Achievements\Models\Achievement;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;
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
        return Cache::remember('achievements_count', 300, function () {
            return static::getModel()::count();
        });
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
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Achievement Name')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'patient' => 'success',
                        'score' => 'warning',
                        'outcome' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'patient' => 'heroicon-o-user',
                        'score' => 'heroicon-o-trophy',
                        'outcome' => 'heroicon-o-chart-bar',
                        default => 'heroicon-o-star',
                    })
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->badge()
                    ->color(fn ($state): string => match(true) {
                        $state >= 100 => 'success',
                        $state >= 50 => 'warning',
                        default => 'info',
                    })
                    ->icon('heroicon-o-star')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable()
                    ->limit(100)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 100) {
                            return $state;
                        }
                        return null;
                    }),

                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->circular()
                    ->size(50),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s')),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Achievement Type')
                    ->options([
                        'patient' => 'Patient',
                        'score' => 'Score',
                        'outcome' => 'Outcome',
                    ])
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('score_range')
                    ->label('Score Range')
                    ->options([
                        'high' => 'High (>= 100)',
                        'medium' => 'Medium (50-99)',
                        'low' => 'Low (< 50)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value'])) {
                            return $query;
                        }
                        return match($data['value']) {
                            'high' => $query->where('score', '>=', 100),
                            'medium' => $query->whereBetween('score', [50, 99]),
                            'low' => $query->where('score', '<', 50),
                            default => $query,
                        };
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->toggleColumnsTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Toggle columns'),
            )
            ->persistFiltersInSession()
            ->deselectAllRecordsWhenFiltered(true)
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Achievement Details')
                    ->modalWidth('3xl'),
                Tables\Actions\EditAction::make()->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->after(function () {
                        Cache::forget('achievements_count');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('achievements_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->emptyStateHeading('No achievements yet')
            ->emptyStateDescription('Achievement badges will appear here when created.')
            ->emptyStateIcon('heroicon-o-trophy');
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
            'view' => Pages\ViewAchievement::route('/{record}'),
            'edit' => Pages\EditAchievement::route('/{record}/edit'),
        ];
    }
}
