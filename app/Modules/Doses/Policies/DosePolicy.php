<?php

namespace App\Modules\Doses\Policies;

use App\Modules\Doses\Models\Dose;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DosePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Dose $dose): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Add more specific authorization logic here if needed
        return $user->hasRole(['Admin', 'Doctor', 'Tester']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Dose $dose): bool
    {
        // Add more specific authorization logic here if needed
        return $user->hasRole(['Admin', 'Doctor', 'Tester']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Dose $dose): bool
    {
        // Add more specific authorization logic here if needed
        return $user->hasRole(['Admin', 'Tester']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Dose $dose): bool
    {
        return $user->hasRole(['Admin', 'Tester']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Dose $dose): bool
    {
        return $user->hasRole(['Admin']);
    }
}
