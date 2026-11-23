<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Internship;
use App\Models\User;

class InternshipPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Internship $internship): bool
    {
        return match ($user->role) {
            UserRole::Admin => $user->is_active,
            UserRole::Lecturer => $user->is_active && $internship->student->supervisorAssignments()
                ->where('lecturer_id', $user->lecturer?->id)
                ->where('status', 'active')
                ->exists(),
            UserRole::Student => $user->is_active && $internship->student->user_id === $user->id,
        };
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->is_active && $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Internship $internship): bool
    {
        return $user->is_active && $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Internship $internship): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Internship $internship): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Internship $internship): bool
    {
        return false;
    }
}
