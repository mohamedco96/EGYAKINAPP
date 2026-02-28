<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class RoleObserver
{
    /**
     * Handle the Role "updated" event.
     * When permissions are synced to a role, mark all users with that role as having permissions changed
     */
    public function updated(Role $role): void
    {
        // Mark all users with this role as having permissions changed
        // This will trigger when permissions are synced via Filament or API
        $this->markUsersPermissionsChanged($role);
    }

    /**
     * Handle the Role "saved" event.
     * This catches permission syncs via relationship
     */
    public function saved(Role $role): void
    {
        // Mark all users with this role as having permissions changed
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


