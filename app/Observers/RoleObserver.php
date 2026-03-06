<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class RoleObserver
{
    /**
     * Handle the Role "updated" event.
     * When a role is updated (e.g. name change), mark all users with that role as having permissions changed.
     * Note: permission syncs via givePermissionTo/revokePermissionTo/syncPermissions are handled explicitly
     * in RolePermissionService and Filament pages, which call markUsersPermissionsChanged directly.
     */
    public function updated(Role $role): void
    {
        $this->markUsersPermissionsChanged($role);
    }

    /**
     * Mark all users with the given role as having permissions changed
     */
    private function markUsersPermissionsChanged(Role $role): void
    {
        try {
            $affectedCount = User::role($role->name)->update(['permissions_changed' => true]);
            
            Log::info('Marked users as having permissions changed', [
                'role_name' => $role->name,
                'affected_users' => $affectedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking users permissions changed', [
                'role_name' => $role->name,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


