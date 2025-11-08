<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Audit Logs';

    protected static ?string $modelLabel = 'Audit Log';

    protected static ?string $pluralModelLabel = 'Audit Logs';

    protected static ?string $navigationGroup = '🔒 System Administration';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('event_type')
                    ->label('Event Type')
                    ->disabled(),

                Forms\Components\TextInput::make('user_name')
                    ->label('User')
                    ->disabled(),

                Forms\Components\TextInput::make('user_email')
                    ->label('Email')
                    ->disabled(),

                Forms\Components\TextInput::make('auditable_type')
                    ->label('Model Type')
                    ->disabled(),

                Forms\Components\TextInput::make('auditable_id')
                    ->label('Model ID')
                    ->disabled(),

                Forms\Components\TextInput::make('ip_address')
                    ->label('IP Address')
                    ->disabled(),

                Forms\Components\TextInput::make('url')
                    ->label('URL')
                    ->disabled(),

                Forms\Components\TextInput::make('method')
                    ->label('HTTP Method')
                    ->disabled(),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->disabled()
                    ->rows(3),

                Forms\Components\KeyValue::make('old_values')
                    ->label('Old Values')
                    ->disabled(),

                Forms\Components\KeyValue::make('new_values')
                    ->label('New Values')
                    ->disabled(),

                Forms\Components\KeyValue::make('metadata')
                    ->label('Metadata')
                    ->disabled(),

                Forms\Components\DateTimePicker::make('performed_at')
                    ->label('Performed At')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('performed_at')
                    ->label('Date/Time')
                    ->dateTime('M j, Y H:i:s')
                    ->sortable()
                    ->searchable(),

                BadgeColumn::make('event_type')
                    ->label('Event')
                    ->colors([
                        'success' => ['created', 'login', 'email_verified'],
                        'warning' => ['updated', 'logout'],
                        'danger' => ['deleted', 'failed_login', 'force_deleted'],
                        'primary' => ['restored', 'api_request'],
                        'secondary' => ['http_read', 'http_request'],
                    ])
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user_name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->tooltip(function (TextColumn $column): ?string {
                        return $column->getState();
                    }),

                TextColumn::make('auditable_type')
                    ->label('Model')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('auditable_id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('method')
                    ->label('Method')
                    ->badge()
                    ->colors([
                        'success' => 'GET',
                        'warning' => 'POST',
                        'danger' => 'DELETE',
                        'primary' => ['PUT', 'PATCH'],
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('url')
                    ->label('URL')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        return $column->getState();
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('device_type')
                    ->label('Device')
                    ->badge()
                    ->colors([
                        'primary' => 'web',
                        'success' => 'mobile',
                        'warning' => 'api',
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        return $column->getState();
                    })
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Event Type')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'login' => 'Login',
                        'logout' => 'Logout',
                        'failed_login' => 'Failed Login',
                        'api_request' => 'API Request',
                        'http_create' => 'HTTP Create',
                        'http_update' => 'HTTP Update',
                        'http_delete' => 'HTTP Delete',
                        'http_read' => 'HTTP Read',
                    ])
                    ->multiple(),

                SelectFilter::make('device_type')
                    ->label('Device Type')
                    ->options([
                        'web' => 'Web',
                        'mobile' => 'Mobile',
                        'api' => 'API',
                    ])
                    ->multiple(),

                Filter::make('date_range')
                    ->form([
                        DateTimePicker::make('from_date')
                            ->label('From Date'),
                        DateTimePicker::make('to_date')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('performed_at', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('performed_at', '<=', $date),
                            );
                    }),

                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading('Audit Log Details')
                    ->modalWidth('7xl'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Audit Logs')
                        ->modalDescription('Are you sure you want to delete these audit logs? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete them'),
                ]),
            ])
            ->defaultSort('performed_at', 'desc')
            ->poll('30s') // Auto-refresh every 30 seconds
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Audit logs should not be manually created
    }

    public static function canEdit($record): bool
    {
        return false; // Audit logs should not be edited
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole(['admin', 'super_admin']) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasRole(['admin', 'super_admin']) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        // Show count of audit logs from today
        return static::getModel()::whereDate('performed_at', today())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
