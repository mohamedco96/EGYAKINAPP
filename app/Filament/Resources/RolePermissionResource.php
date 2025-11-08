<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RolePermissionResource\Pages;
use App\Modules\RolePermission\Models\RolePermission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class RolePermissionResource extends Resource
{
    protected static ?string $model = RolePermission::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Role Permissions';
    protected static ?string $navigationGroup = '🔐 Access Control';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('role_permissions_count', 300, fn() => static::getModel()::count());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Assignment')->schema([
                Forms\Components\Select::make('role_id')->relationship('role', 'name')->searchable()->preload()->required(),
                Forms\Components\Select::make('permission_id')->relationship('permission', 'name')->searchable()->preload()->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('role.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('permission.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make(), ExportBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRolePermissions::route('/'),
            'create' => Pages\CreateRolePermission::route('/create'),
            'view' => Pages\ViewRolePermission::route('/{record}'),
            'edit' => Pages\EditRolePermission::route('/{record}/edit'),
        ];
    }
}
