<?php

namespace App\Policies;

use App\Enums\AssessmentStatus;
use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\User;

class AssessmentPolicy
{
    public function view(User $user, Assessment $assessment): bool
    {
        if ($user->role === UserRole::Student) {
            return $assessment->status === AssessmentStatus::Published
                && $user->student?->id === $assessment->weeklyReport->reportPeriod->internship->student_id;
        }

        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $user->role === UserRole::Lecturer
            && $user->lecturer?->id === $assessment->lecturer_id
            && $assessment->weeklyReport->reportPeriod->internship->student->supervisorAssignments()
                ->where('lecturer_id', $user->lecturer->id)->exists();
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $user->role === UserRole::Lecturer
            && $assessment->status === AssessmentStatus::Draft
            && $assessment->lecturer_id === $user->lecturer?->id;
    }
}
