<?php

namespace App\Filament\Widgets;

use App\Enums\AssessmentStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\ReportPeriod;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Models\WeeklyReport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AdminOverviewStats extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $currentPeriods = ReportPeriod::query()
            ->whereDate('starts_on', '<=', today())
            ->whereDate('ends_on', '>=', today())
            ->whereHas('internship', fn (Builder $query) => $query->where('status', 'active'));

        $reports = WeeklyReport::query()->whereHas('reportPeriod', fn (Builder $query) => $query
            ->whereDate('starts_on', '<=', today())
            ->whereDate('ends_on', '>=', today())
            ->whereHas('internship', fn (Builder $internshipQuery) => $internshipQuery->where('status', 'active')));

        return [
            Stat::make('Mahasiswa aktif', User::query()->where('role', UserRole::Student)->where('is_active', true)->count()),
            Stat::make('Dosen aktif', User::query()->where('role', UserRole::Lecturer)->where('is_active', true)->count()),
            Stat::make('Mahasiswa tanpa pembimbing aktif', User::query()
                ->where('role', UserRole::Student)
                ->where('is_active', true)
                ->whereDoesntHave('student.supervisorAssignments', fn ($query) => $query->where('status', 'active'))
                ->count()),
            Stat::make('Penugasan pembimbing aktif', SupervisorAssignment::query()->where('status', 'active')->count()),
            Stat::make('Laporan belum dikumpulkan melewati batas', (clone $currentPeriods)
                ->where('due_at', '<', now())
                ->whereDoesntHave('weeklyReport.latestVersion')
                ->count()),
            Stat::make('Laporan terlambat', (clone $currentPeriods)
                ->whereHas('weeklyReport.latestVersion', fn (Builder $query) => $query->where('is_late', true))
                ->count()),
            Stat::make('Laporan menunggu pemeriksaan', (clone $reports)->where('review_status', ReviewStatus::PendingReview)->count()),
            Stat::make('Laporan perlu revisi', (clone $reports)->where('review_status', ReviewStatus::RevisionRequested)->count()),
            Stat::make('Penilaian draf', Assessment::query()
                ->where('status', AssessmentStatus::Draft)
                ->whereHas('weeklyReport.reportPeriod', fn (Builder $query) => $query
                    ->whereDate('starts_on', '<=', today())
                    ->whereDate('ends_on', '>=', today())
                    ->whereHas('internship', fn (Builder $internshipQuery) => $internshipQuery->where('status', 'active')))
                ->count()),
            Stat::make('Penilaian dipublikasikan', Assessment::query()
                ->where('status', AssessmentStatus::Published)
                ->whereHas('weeklyReport.reportPeriod', fn (Builder $query) => $query
                    ->whereDate('starts_on', '<=', today())
                    ->whereDate('ends_on', '>=', today())
                    ->whereHas('internship', fn (Builder $internshipQuery) => $internshipQuery->where('status', 'active')))
                ->count()),
        ];
    }
}
