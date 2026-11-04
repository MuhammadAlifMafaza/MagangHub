<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ReportPeriod;
use App\Models\ReportVersion;
use App\Models\User;
use App\Models\WeeklyReport;

class WeeklyReportPolicy
{
    public function submit(User $user, ReportPeriod $period): bool
    {
        return $user->role === UserRole::Student && $user->student?->id === $period->internship->student_id;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Lecturer, UserRole::Student], true);
    }

    public function viewPeriod(User $user, ReportPeriod $period): bool
    {
        if ($user->role === UserRole::Student) {
            return $user->student?->id === $period->internship->student_id;
        }

        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $user->role === UserRole::Lecturer && $this->hasAssignment($user, $period, false);
    }

    public function view(User $user, WeeklyReport $report): bool
    {
        return $this->viewPeriod($user, $report->reportPeriod);
    }

    public function review(User $user, WeeklyReport $report): bool
    {
        return $user->role === UserRole::Lecturer && $user->lecturer !== null && $this->hasAssignment($user, $report->reportPeriod, true);
    }

    public function assess(User $user, WeeklyReport $report): bool
    {
        return $this->review($user, $report);
    }

    public function download(User $user, ReportVersion $version): bool
    {
        return $this->view($user, $version->weeklyReport);
    }

    private function hasAssignment(User $user, ReportPeriod $period, bool $activeOnly): bool
    {
        $query = $period->internship->student->supervisorAssignments()->where('lecturer_id', $user->lecturer?->id);
        if ($activeOnly) {
            $query->where('status', 'active');
        }

        return $user->lecturer !== null && $query->exists();
    }
}
