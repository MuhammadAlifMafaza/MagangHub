<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\GuidanceSlot;
use App\Models\User;

class GuidanceSlotPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Lecturer;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GuidanceSlot $guidanceSlot): bool
    {
        return $user->role === UserRole::Lecturer && $guidanceSlot->lecturer->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Lecturer && $user->lecturer()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GuidanceSlot $guidanceSlot): bool
    {
        return $user->role === UserRole::Lecturer && $guidanceSlot->lecturer->user_id === $user->id && $guidanceSlot->status->value === 'open';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GuidanceSlot $guidanceSlot): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GuidanceSlot $guidanceSlot): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GuidanceSlot $guidanceSlot): bool
    {
        return false;
    }
}
