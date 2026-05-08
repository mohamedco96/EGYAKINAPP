<?php

namespace App\Modules\Achievements\Filament\Resources;

use App\Modules\Achievements\Filament\Resources\AchievementResource\Pages\CreateAchievement;
use App\Modules\Achievements\Filament\Resources\AchievementResource\Pages\EditAchievement;
use App\Modules\Achievements\Filament\Resources\AchievementResource\Pages\ListAchievements;
use App\Modules\Achievements\Filament\Resources\AchievementResource\Pages\ViewAchievement;
use App\Modules\Achievements\Models\Achievement;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AchievementResource extends Resource
{
    protected static ?string $model = Achievement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Achievements';

    protected static string|\UnitEnum|null $navigationGroup = 'App Data';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('achievements_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required(),
                TextInput::make('description')
                    ->label('Description'),
                Select::make('type')
                    ->label('Achievement Type')
                    ->options([
                        'patient' => 'Patient',
                        'score' => 'Score',
                        'outcome' => 'Outcome',
                    ]),
                TextInput::make('score')
                    ->label('Achievement Score')
                    ->required(),
                FileUpload::make('image')
                    ->label('Achievement Image')
                    ->directory('achievement_images')
                    ->visibility('public')
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
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Achievement Name')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('type')
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

                TextColumn::make('score')
                    ->label('Score')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 100 => 'success',
                        $state >= 50 => 'warning',
                        default => 'info',
                    })
                    ->icon('heroicon-o-star')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable()
                    ->limit(100)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 100) {
                            return $state;
                        }

                        return null;
                    }),

                ImageColumn::make('image')
                    ->label('Image')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->circular()
                    ->size(50),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s')),

                TextColumn::make('updated_at')
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
                SelectFilter::make('type')
                    ->label('Achievement Type')
                    ->options([
                        'patient' => 'Patient',
                        'score' => 'Score',
                        'outcome' => 'Outcome',
                    ])
                    ->multiple()
                    ->searchable(),

                SelectFilter::make('score_range')
                    ->label('Score Range')
                    ->options([
                        'high' => 'High (>= 100)',
                        'medium' => 'Medium (50-99)',
                        'low' => 'Low (< 50)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'high' => $query->where('score', '>=', 100),
                            'medium' => $query->whereBetween('score', [50, 99]),
                            'low' => $query->where('score', '<', 50),
                            default => $query,
                        };
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Toggle columns'),
            )
            ->persistFiltersInSession()
            ->deferFilters(false)
            ->deselectAllRecordsWhenFiltered(true)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Achievement Details')
                    ->modalWidth('3xl'),
                EditAction::make()->icon('heroicon-o-pencil'),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->after(function () {
                        Cache::forget('achievements_count');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('achievements_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
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
            'index' => ListAchievements::route('/'),
            'create' => CreateAchievement::route('/create'),
            'view' => ViewAchievement::route('/{record}'),
            'edit' => EditAchievement::route('/{record}/edit'),
        ];
    }
}
