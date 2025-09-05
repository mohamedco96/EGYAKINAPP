<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use App\Filament\Widgets\RolePermissionChartWidget;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Spatie\Permission\Models\Permission;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_default_permissions')
                ->label('Create Default Permissions')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('info')
                ->action(function () {
                    $defaultPermissions = [
                        // User Management
                        ['name' => 'view-users', 'guard_name' => 'web'],
                        ['name' => 'create-users', 'guard_name' => 'web'],
                        ['name' => 'edit-users', 'guard_name' => 'web'],
                        ['name' => 'delete-users', 'guard_name' => 'web'],

                        // Role Management
                        ['name' => 'view-roles', 'guard_name' => 'web'],
                        ['name' => 'create-roles', 'guard_name' => 'web'],
                        ['name' => 'edit-roles', 'guard_name' => 'web'],
                        ['name' => 'delete-roles', 'guard_name' => 'web'],

                        // Permission Management
                        ['name' => 'view-permissions', 'guard_name' => 'web'],
                        ['name' => 'create-permissions', 'guard_name' => 'web'],
                        ['name' => 'edit-permissions', 'guard_name' => 'web'],
                        ['name' => 'delete-permissions', 'guard_name' => 'web'],

                        // Content Management
                        ['name' => 'view-posts', 'guard_name' => 'web'],
                        ['name' => 'create-posts', 'guard_name' => 'web'],
                        ['name' => 'edit-posts', 'guard_name' => 'web'],
                        ['name' => 'delete-posts', 'guard_name' => 'web'],

                        // System
                        ['name' => 'access-admin', 'guard_name' => 'web'],
                        ['name' => 'view-reports', 'guard_name' => 'web'],
                        ['name' => 'manage-settings', 'guard_name' => 'web'],
                    ];

                    $created = 0;
                    foreach ($defaultPermissions as $permissionData) {
                        if (! Permission::where('name', $permissionData['name'])->exists()) {
                            Permission::create($permissionData);
                            $created++;
                        }
                    }

                    Notification::make()
                        ->title($created > 0 ? "Created {$created} default permissions" : 'All default permissions already exist')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Create Default Permissions')
                ->modalDescription('This will create standard CRUD permissions for users, roles, permissions, posts, and system access.'),

            Actions\CreateAction::make()
                ->label('New Permission')
                ->icon('heroicon-o-plus')
                ->color('success'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RolePermissionChartWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Permission Management';
    }

    public function getSubheading(): ?string
    {
        return 'Manage system permissions and access control';
    }
}
