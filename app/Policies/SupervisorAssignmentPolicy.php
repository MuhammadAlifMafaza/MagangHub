<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SupervisorAssignment;
use App\Models\User;

class SupervisorAssignmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SupervisorAssignment $supervisorAssignment): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Lecturer => $user->lecturer?->id === $supervisorAssignment->lecturer_id,
            UserRole::Student => $user->student?->id === $supervisorAssignment->student_id,
        };
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SupervisorAssignment $supervisorAssignment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SupervisorAssignment $supervisorAssignment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SupervisorAssignment $supervisorAssignment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SupervisorAssignment $supervisorAssignment): bool
    {
        return false;
    }
}
