<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RolePermissionResource\Pages\CreateRolePermission;
use App\Filament\Resources\RolePermissionResource\Pages\EditRolePermission;
use App\Filament\Resources\RolePermissionResource\Pages\ListRolePermissions;
use App\Filament\Resources\RolePermissionResource\Pages\ViewRolePermission;
use App\Modules\RolePermission\Models\RolePermission;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class RolePermissionResource extends Resource
{
    protected static ?string $model = RolePermission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Role Permissions';

    protected static string|\UnitEnum|null $navigationGroup = '🔐 Access Control';

    protected static ?int $navigationSort = 3;

    /**
     * Hide this resource from navigation
     * Role-permission assignments are managed through Role and Permission resources
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('role_permissions_count', 300, fn () => static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Assignment')->schema([
                Select::make('role_id')->relationship('role', 'name')->searchable()->preload()->required(),
                Select::make('permission_id')->relationship('permission', 'name')->searchable()->preload()->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['role', 'permission']))
            ->columns([
                TextColumn::make('role.name')
                    ->label('Role')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('permission.name')
                    ->label('Permission')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('permission.category')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->sortable(),
            ])
            ->defaultSort('role_id')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRolePermissions::route('/'),
            'create' => CreateRolePermission::route('/create'),
            'view' => ViewRolePermission::route('/{record}'),
            'edit' => EditRolePermission::route('/{record}/edit'),
        ];
    }
}
