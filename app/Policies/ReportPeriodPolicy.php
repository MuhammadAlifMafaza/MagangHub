<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ReportPeriod;
use App\Models\SupervisorAssignment;
use App\Models\User;

class ReportPeriodPolicy
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
    public function view(User $user, ReportPeriod $reportPeriod): bool
    {
        return match ($user->role) {
            UserRole::Admin => $user->is_active,
            UserRole::Lecturer => $user->is_active && SupervisorAssignment::query()
                ->where('student_id', $reportPeriod->internship->student_id)
                ->where('lecturer_id', $user->lecturer?->id)
                ->coveringInstant($reportPeriod->starts_on->copy()->startOfDay())
                ->exists(),
            UserRole::Student => $user->is_active && $reportPeriod->internship->student->user_id === $user->id,
        };
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReportPeriod $reportPeriod): bool
    {
        return $user->is_active && $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ReportPeriod $reportPeriod): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ReportPeriod $reportPeriod): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ReportPeriod $reportPeriod): bool
    {
        return false;
    }
}
