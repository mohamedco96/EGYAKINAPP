<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingsResource\Pages;
use App\Modules\Settings\Models\Settings;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SettingsResource extends Resource
{
    protected static ?string $model = Settings::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'App Settings';

    protected static ?string $navigationGroup = '🔒 System Administration';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('settings_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Application Control')
                    ->description('Global application settings and controls')
                    ->schema([
                        Forms\Components\Toggle::make('app_freeze')
                            ->label('App Freeze')
                            ->helperText('When enabled, the mobile app will be frozen for all users')
                            ->inline(false)
                            ->default(false),

                        Forms\Components\Toggle::make('force_update')
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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\IconColumn::make('app_freeze')
                    ->label('App Frozen')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('app_freeze')
                    ->label('Freeze Status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'FROZEN' : 'Active')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\IconColumn::make('force_update')
                    ->label('Force Update')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('force_update')
                    ->label('Update Status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'warning' : 'success')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Required' : 'Optional')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-arrow-path' : 'heroicon-o-check-circle')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('updated_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\TernaryFilter::make('app_freeze')
                    ->label('App Freeze Status')
                    ->placeholder('All settings')
                    ->trueLabel('Frozen only')
                    ->falseLabel('Active only'),

                Tables\Filters\TernaryFilter::make('force_update')
                    ->label('Force Update Status')
                    ->placeholder('All settings')
                    ->trueLabel('Update required')
                    ->falseLabel('Update optional'),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Toggle columns'),
            )
            ->persistFiltersInSession()
            ->deselectAllRecordsWhenFiltered(true)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Settings Details')
                    ->modalWidth('3xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleFreeze')
                    ->label(fn ($record) => $record->app_freeze ? 'Unfreeze App' : 'Freeze App')
                    ->icon(fn ($record) => $record->app_freeze ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn ($record) => $record->app_freeze ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->app_freeze ? 'Unfreeze Application?' : 'Freeze Application?')
                    ->modalDescription(fn ($record) => $record->app_freeze
                        ? 'This will allow users to access the app again.'
                        : 'This will prevent all users from accessing the app.')
                    ->action(function ($record) {
                        $record->update(['app_freeze' => !$record->app_freeze]);
                    })
                    ->successNotificationTitle('App freeze status updated'),
                Tables\Actions\Action::make('toggleForceUpdate')
                    ->label(fn ($record) => $record->force_update ? 'Disable Force Update' : 'Enable Force Update')
                    ->icon('heroicon-o-arrow-path')
                    ->color(fn ($record) => $record->force_update ? 'success' : 'warning')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['force_update' => !$record->force_update]);
                    })
                    ->successNotificationTitle('Force update status updated'),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Cache::forget('settings_count');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('settings_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSettings::route('/create'),
            'view' => Pages\ViewSettings::route('/{record}'),
            'edit' => Pages\EditSettings::route('/{record}/edit'),
        ];
    }
}
