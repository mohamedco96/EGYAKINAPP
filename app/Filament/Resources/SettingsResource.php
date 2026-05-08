<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingsResource\Pages\CreateSettings;
use App\Filament\Resources\SettingsResource\Pages\EditSettings;
use App\Filament\Resources\SettingsResource\Pages\ListSettings;
use App\Filament\Resources\SettingsResource\Pages\ViewSettings;
use App\Modules\Settings\Models\Settings;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SettingsResource extends Resource
{
    protected static ?string $model = Settings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'App Settings';

    protected static string|\UnitEnum|null $navigationGroup = '⚙️ Administration';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('settings_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Application Control')
                    ->description('Global application settings and controls')
                    ->schema([
                        Toggle::make('app_freeze')
                            ->label('App Freeze')
                            ->helperText('When enabled, the mobile app will be frozen for all users')
                            ->inline(false)
                            ->default(false),

                        Toggle::make('force_update')
                            ->label('Force Update')
                            ->helperText('When enabled, users will be forced to update the mobile app')
                            ->inline(false)
                            ->default(false),
                    ])->columns(2),
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
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('app_freeze')
                    ->label('App Frozen')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('app_freeze_status')
                    ->label('Freeze Status')
                    ->getStateUsing(fn ($record) => $record->app_freeze)
                    ->badge()
                    ->color(fn ($state): string => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state): string => $state ? 'FROZEN' : 'Active')
                    ->icon(fn ($state): string => $state ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('force_update')
                    ->label('Force Update')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('force_update_status')
                    ->label('Update Status')
                    ->getStateUsing(fn ($record) => $record->force_update)
                    ->badge()
                    ->color(fn ($state): string => $state ? 'warning' : 'success')
                    ->formatStateUsing(fn ($state): string => $state ? 'Required' : 'Optional')
                    ->icon(fn ($state): string => $state ? 'heroicon-o-arrow-path' : 'heroicon-o-check-circle')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('updated_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                TernaryFilter::make('app_freeze')
                    ->label('App Freeze Status')
                    ->placeholder('All settings')
                    ->trueLabel('Frozen only')
                    ->falseLabel('Active only'),

                TernaryFilter::make('force_update')
                    ->label('Force Update Status')
                    ->placeholder('All settings')
                    ->trueLabel('Update required')
                    ->falseLabel('Update optional'),
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
                    ->modalHeading('Settings Details')
                    ->modalWidth('3xl'),
                EditAction::make(),
                Action::make('toggleFreeze')
                    ->label(fn ($record) => $record->app_freeze ? 'Unfreeze App' : 'Freeze App')
                    ->icon(fn ($record) => $record->app_freeze ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn ($record) => $record->app_freeze ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->app_freeze ? 'Unfreeze Application?' : 'Freeze Application?')
                    ->modalDescription(fn ($record) => $record->app_freeze
                        ? 'This will allow users to access the app again.'
                        : 'This will prevent all users from accessing the app.')
                    ->action(function ($record) {
                        $record->update(['app_freeze' => ! $record->app_freeze]);
                    })
                    ->successNotificationTitle('App freeze status updated'),
                Action::make('toggleForceUpdate')
                    ->label(fn ($record) => $record->force_update ? 'Disable Force Update' : 'Enable Force Update')
                    ->icon('heroicon-o-arrow-path')
                    ->color(fn ($record) => $record->force_update ? 'success' : 'warning')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['force_update' => ! $record->force_update]);
                    })
                    ->successNotificationTitle('Force update status updated'),
                DeleteAction::make()
                    ->after(function () {
                        Cache::forget('settings_count');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('settings_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->emptyStateHeading('No settings configured')
            ->emptyStateDescription('Create your first app setting configuration.')
            ->emptyStateIcon('heroicon-o-cog-6-tooth');
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
            'index' => ListSettings::route('/'),
            'create' => CreateSettings::route('/create'),
            'view' => ViewSettings::route('/{record}'),
            'edit' => EditSettings::route('/{record}/edit'),
        ];
    }
}
