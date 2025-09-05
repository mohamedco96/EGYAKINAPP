<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRoles = Role::count();
        $totalPermissions = Permission::count();
        $totalUsers = User::count();
        $usersWithRoles = User::whereHas('roles')->count();
        $rolesWithPermissions = Role::whereHas('permissions')->count();
        $permissionsInUse = Permission::whereHas('roles')->count();

        return [
            Stat::make('Total Roles', $totalRoles)
                ->description($rolesWithPermissions.' have permissions')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Total Permissions', $totalPermissions)
                ->description($permissionsInUse.' assigned to roles')
                ->descriptionIcon('heroicon-m-key')
                ->color('info')
                ->chart([15, 4, 10, 2, 12, 4, 12]),

            Stat::make('Users with Roles', $usersWithRoles)
                ->description('Out of '.$totalUsers.' total users')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning')
                ->chart([2, 10, 3, 15, 4, 17, 7]),

            Stat::make('Role Coverage', $totalRoles > 0 ? round(($rolesWithPermissions / $totalRoles) * 100).'%' : '0%')
                ->description('Roles with permissions')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($rolesWithPermissions / max($totalRoles, 1) > 0.8 ? 'success' : 'danger'),

            Stat::make('Permission Usage', $totalPermissions > 0 ? round(($permissionsInUse / $totalPermissions) * 100).'%' : '0%')
                ->description('Permissions in use')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($permissionsInUse / max($totalPermissions, 1) > 0.7 ? 'success' : 'warning'),

            Stat::make('User Assignment', $totalUsers > 0 ? round(($usersWithRoles / $totalUsers) * 100).'%' : '0%')
                ->description('Users with roles')
                ->descriptionIcon('heroicon-m-user-group')
                ->color($usersWithRoles / max($totalUsers, 1) > 0.5 ? 'success' : 'danger'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
