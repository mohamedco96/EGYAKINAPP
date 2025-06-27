<?php

namespace App\Modules\Chat\Policies;

use App\Modules\Chat\Models\DoctorMonthlyTrial;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DoctorMonthlyTrialPolicy
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
    public function view(User $user, DoctorMonthlyTrial $doctorMonthlyTrial): bool
    {
        // Users can only view their own trial information
        return $user->id === $doctorMonthlyTrial->doctor_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DoctorMonthlyTrial $doctorMonthlyTrial): bool
    {
        // Users can only update their own trial information
        return $user->id === $doctorMonthlyTrial->doctor_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DoctorMonthlyTrial $doctorMonthlyTrial): bool
    {
        // Users can only delete their own trial information
        return $user->id === $doctorMonthlyTrial->doctor_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DoctorMonthlyTrial $doctorMonthlyTrial): bool
    {
        return $user->id === $doctorMonthlyTrial->doctor_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DoctorMonthlyTrial $doctorMonthlyTrial): bool
    {
        return $user->id === $doctorMonthlyTrial->doctor_id;
    }
}
