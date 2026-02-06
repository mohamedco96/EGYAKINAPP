<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FcmTokenResource\Pages;
use App\Modules\Notifications\Models\FcmToken;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class FcmTokenResource extends Resource
{
    protected static ?string $model = FcmToken::class;
    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'FCM Tokens';
    protected static ?string $navigationGroup = '🔒 System Administration';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('fcm_tokens_count', 300, fn() => static::getModel()::count());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Token Information')
                ->description('FCM (Firebase Cloud Messaging) token details')
                ->schema([
                    Forms\Components\Select::make('doctor_id')
                        ->relationship('doctor', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Doctor')
                        ->helperText('Select the doctor/user who owns this token'),

                    Forms\Components\Textarea::make('token')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull()
                        ->label('FCM Token')
                        ->helperText('Firebase Cloud Messaging token for push notifications')
                        ->placeholder('Enter the FCM token...'),

                    Forms\Components\TextInput::make('device_id')
                        ->label('Device ID')
                        ->maxLength(50)
                        ->helperText('Unique device identifier'),

                    Forms\Components\Select::make('device_type')
                        ->label('Device Type')
                        ->options([
                            'ios' => 'iOS',
                            'android' => 'Android',
                            'web' => 'Web',
                        ])
                        ->native(false)
                        ->helperText('Type of device'),

                    Forms\Components\TextInput::make('app_version')
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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable(['users.name', 'users.lname'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name . ' ' . ($record->doctor->lname ?? '') : 'N/A')
                    ->description(fn ($record) => $record->doctor?->email)
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('token')
                    ->label('FCM Token')
                    ->limit(40)
                    ->copyable()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 40) {
                            return $state;
                        }
                        return null;
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('device_id')
                    ->label('Device ID')
                    ->limit(20)
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('device_type')
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

                Tables\Columns\TextColumn::make('app_version')
                    ->label('App Version')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('device_type')
                    ->label('Device Type')
                    ->options([
                        'ios' => 'iOS',
                        'android' => 'Android',
                        'web' => 'Web',
                    ])
                    ->multiple()
                    ->searchable(),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Cache::forget('fcm_tokens_count');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('fcm_tokens_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->emptyStateHeading('No FCM tokens registered')
            ->emptyStateDescription('Device FCM tokens for push notifications will appear here.')
            ->emptyStateIcon('heroicon-o-device-phone-mobile');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFcmTokens::route('/'),
            'create' => Pages\CreateFcmToken::route('/create'),
            'view' => Pages\ViewFcmToken::route('/{record}'),
            'edit' => Pages\EditFcmToken::route('/{record}/edit'),
        ];
    }
}
