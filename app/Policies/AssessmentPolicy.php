<?php

namespace App\Policies;

use App\Enums\AssessmentStatus;
use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\SupervisorAssignment;
use App\Models\User;

class AssessmentPolicy
{
    public function view(User $user, Assessment $assessment): bool
    {
        if ($user->role === UserRole::Student) {
            return $user->is_active && $assessment->status === AssessmentStatus::Published
                && $user->student?->id === $assessment->weeklyReport->reportPeriod->internship->student_id;
        }

        if ($user->role === UserRole::Admin) {
            return $user->is_active;
        }

        return $user->is_active
            && $user->lecturer?->id === $assessment->lecturer_id
            && SupervisorAssignment::query()
                ->where('student_id', $assessment->weeklyReport->reportPeriod->internship->student_id)
                ->where('lecturer_id', $user->lecturer->id)
                ->coveringInstant($assessment->weeklyReport->reportPeriod->starts_on->copy()->startOfDay())
                ->exists();
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $user->is_active && $user->role === UserRole::Lecturer
            && $assessment->status === AssessmentStatus::Draft
            && $assessment->lecturer_id === $user->lecturer?->id
            && SupervisorAssignment::query()
                ->where('student_id', $assessment->weeklyReport->reportPeriod->internship->student_id)
                ->where('lecturer_id', $user->lecturer->id)
                ->coveringInstant($assessment->weeklyReport->reportPeriod->starts_on->copy()->startOfDay())
                ->exists();
    }
}
