<?php

namespace App\Modules\Chat\Policies;

use App\Modules\Chat\Models\AIConsultation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AIConsultationPolicy
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
    public function view(User $user, AIConsultation $aiConsultation): bool
    {
        // Users can only view their own consultations
        return $user->id === $aiConsultation->doctor_id;
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
    public function update(User $user, AIConsultation $aiConsultation): bool
    {
        // Users can only update their own consultations
        return $user->id === $aiConsultation->doctor_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AIConsultation $aiConsultation): bool
    {
        // Users can only delete their own consultations
        return $user->id === $aiConsultation->doctor_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AIConsultation $aiConsultation): bool
    {
        return $user->id === $aiConsultation->doctor_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AIConsultation $aiConsultation): bool
    {
        return $user->id === $aiConsultation->doctor_id;
    }
}
