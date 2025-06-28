<?php

namespace App\Modules\RolePermission\Policies;

use App\Modules\RolePermission\Models\RolePermission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RolePermissionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view role permissions') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RolePermission $rolePermission): bool
    {
        return $user->hasPermissionTo('view role permissions') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create role permissions') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RolePermission $rolePermission): bool
    {
        return $user->hasPermissionTo('update role permissions') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RolePermission $rolePermission): bool
    {
        return $user->hasPermissionTo('delete role permissions') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RolePermission $rolePermission): bool
    {
        return $user->hasPermissionTo('restore role permissions') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RolePermission $rolePermission): bool
    {
        return $user->hasPermissionTo('force delete role permissions') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can manage roles and permissions.
     */
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('manage roles and permissions') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can assign roles to users.
     */
    public function assignRole(User $user): bool
    {
        return $user->hasPermissionTo('assign roles') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can assign permissions to users.
     */
    public function assignPermission(User $user): bool
    {
        return $user->hasPermissionTo('assign permissions') || $user->hasRole('admin');
    }
}
