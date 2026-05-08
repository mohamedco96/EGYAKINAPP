<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FcmTokenResource\Pages\CreateFcmToken;
use App\Filament\Resources\FcmTokenResource\Pages\EditFcmToken;
use App\Filament\Resources\FcmTokenResource\Pages\ListFcmTokens;
use App\Filament\Resources\FcmTokenResource\Pages\ViewFcmToken;
use App\Modules\Notifications\Models\FcmToken;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class FcmTokenResource extends Resource
{
    protected static ?string $model = FcmToken::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationLabel = 'FCM Tokens';

    protected static string|\UnitEnum|null $navigationGroup = '⚙️ Administration';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('fcm_tokens_count', 300, fn () => static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Token Information')
                ->description('FCM (Firebase Cloud Messaging) token details')
                ->schema([
                    Select::make('doctor_id')
                        ->relationship('doctor', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Doctor')
                        ->helperText('Select the doctor/user who owns this token'),

                    Textarea::make('token')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull()
                        ->label('FCM Token')
                        ->helperText('Firebase Cloud Messaging token for push notifications')
                        ->placeholder('Enter the FCM token...'),

                    TextInput::make('device_id')
                        ->label('Device ID')
                        ->maxLength(50)
                        ->helperText('Unique device identifier'),

                    Select::make('device_type')
                        ->label('Device Type')
                        ->options([
                            'ios' => 'iOS',
                            'android' => 'Android',
                            'web' => 'Web',
                        ])
                        ->native(false)
                        ->helperText('Type of device'),

                    TextInput::make('app_version')
                        ->label('App Version')
                        ->maxLength(20)
                        ->helperText('Application version'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['doctor']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable(['users.name', 'users.lname'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name.' '.($record->doctor->lname ?? '') : 'N/A')
                    ->description(fn ($record) => $record->doctor?->email)
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('token')
                    ->label('FCM Token')
                    ->limit(40)
                    ->copyable()
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 40) {
                            return $state;
                        }

                        return null;
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('device_id')
                    ->label('Device ID')
                    ->limit(20)
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('device_type')
                    ->label('Device')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ios' => 'info',
                        'android' => 'success',
                        'web' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('app_version')
                    ->label('App Version')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('device_type')
                    ->label('Device Type')
                    ->options([
                        'ios' => 'iOS',
                        'android' => 'Android',
                        'web' => 'Web',
                    ])
                    ->multiple()
                    ->searchable(),
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
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->after(function () {
                        Cache::forget('fcm_tokens_count');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('fcm_tokens_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->emptyStateHeading('No FCM tokens registered')
            ->emptyStateDescription('Device FCM tokens for push notifications will appear here.')
            ->emptyStateIcon('heroicon-o-device-phone-mobile');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFcmTokens::route('/'),
            'create' => CreateFcmToken::route('/create'),
            'view' => ViewFcmToken::route('/{record}'),
            'edit' => EditFcmToken::route('/{record}/edit'),
        ];
    }
}
