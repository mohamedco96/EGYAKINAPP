<?php

namespace App\Filament\Resources\RolePermissionResource\Pages;

use App\Filament\Resources\RolePermissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListRolePermissions extends ListRecords
{
    protected static string $resource = RolePermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->orderBy('role_id')->orderBy('permission_id');
    }

    protected function afterDelete(): void
    {
        Cache::forget('role_permissions_count');
    }
}
