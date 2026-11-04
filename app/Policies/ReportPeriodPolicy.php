<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ReportPeriod;
use App\Models\User;

class ReportPeriodPolicy
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
    public function view(User $user, ReportPeriod $reportPeriod): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Lecturer => $reportPeriod->internship->student->supervisorAssignments()
                ->where('lecturer_id', $user->lecturer?->id)
                ->whereIn('status', ['active', config('magang.supervisor_assignments.lecturer_historical_read_status', 'ended')])
                ->exists(),
            UserRole::Student => $reportPeriod->internship->student->user_id === $user->id,
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
        return $user->role === UserRole::Admin;
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
