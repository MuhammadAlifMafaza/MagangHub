<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ReportPeriod;
use App\Models\ReportVersion;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Models\WeeklyReport;

class WeeklyReportPolicy
{
    public function submit(User $user, ReportPeriod $period): bool
    {
        return $user->is_active && $user->role === UserRole::Student && $user->student?->id === $period->internship->student_id;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role, [UserRole::Admin, UserRole::Lecturer, UserRole::Student], true);
    }

    public function viewPeriod(User $user, ReportPeriod $period): bool
    {
        if ($user->role === UserRole::Student) {
            return $user->is_active && $user->student?->id === $period->internship->student_id;
        }

        if ($user->role === UserRole::Admin) {
            return $user->is_active;
        }

        return $user->is_active && $this->hasAssignment($user, $period);
    }

    public function view(User $user, WeeklyReport $report): bool
    {
        return $this->viewPeriod($user, $report->reportPeriod);
    }

    public function review(User $user, WeeklyReport $report): bool
    {
        return $user->is_active && $user->role === UserRole::Lecturer && $user->lecturer !== null && $this->hasAssignment($user, $report->reportPeriod);
    }

    public function assess(User $user, WeeklyReport $report): bool
    {
        return $this->review($user, $report);
    }

    public function download(User $user, ReportVersion $version): bool
    {
        return $this->view($user, $version->weeklyReport);
    }

    private function hasAssignment(User $user, ReportPeriod $period): bool
    {
        return $user->lecturer !== null && SupervisorAssignment::query()
            ->where('student_id', $period->internship->student_id)
            ->where('lecturer_id', $user->lecturer->id)
            ->coveringInstant($period->starts_on->copy()->startOfDay())
            ->exists();
    }
}
