<?php

namespace App\Filament\Resources\RolePermissionResource\Pages;

use App\Filament\Resources\RolePermissionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateRolePermission extends CreateRecord
{
    protected static string $resource = RolePermissionResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('role_permissions_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}