<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Filament\Widgets\RolePermissionStatsWidget;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Spatie\Permission\Models\Role;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_default_roles')
                ->label('Create Default Roles')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('info')
                ->action(function () {
                    $defaultRoles = [
                        ['name' => 'super-admin', 'guard_name' => 'web'],
                        ['name' => 'admin', 'guard_name' => 'web'],
                        ['name' => 'editor', 'guard_name' => 'web'],
                        ['name' => 'user', 'guard_name' => 'web'],
                    ];

                    $created = 0;
                    foreach ($defaultRoles as $roleData) {
                        if (! Role::where('name', $roleData['name'])->exists()) {
                            Role::create($roleData);
                            $created++;
                        }
                    }

                    Notification::make()
                        ->title($created > 0 ? "Created {$created} default roles" : 'All default roles already exist')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Create Default Roles')
                ->modalDescription('This will create standard roles: Super Admin, Admin, Editor, and User (if they don\'t exist).'),

            Actions\CreateAction::make()
                ->label('New Role')
                ->icon('heroicon-o-plus')
                ->color('success'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RolePermissionStatsWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Role Management';
    }

    public function getSubheading(): ?string
    {
        return 'Manage user roles and their permissions';
    }
}
