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
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class AdminOverviewStats extends Widget
{
    protected string $view = 'filament.widgets.admin-overview-stats';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    /**
     * @return array{
     *     primaryStats: array<array{label: string, value: int|string, description: string, color: string}>,
     *     secondaryStats: array<array{label: string, value: int, color: string}>,
     *     reportStatuses: array<array{label: string, color: string, count: int}>,
     *     recapUrl: string
     * }
     */
    protected function getViewData(): array
    {
        $currentPeriods = ReportPeriod::query()
            ->whereDate('starts_on', '<=', today())
            ->whereDate('ends_on', '>=', today())
            ->whereHas('internship', fn (Builder $query) => $query->where('status', 'active'));

        $reports = WeeklyReport::query()->whereHas('reportPeriod', fn (Builder $query) => $query
            ->whereDate('starts_on', '<=', today())
            ->whereDate('ends_on', '>=', today())
            ->whereHas('internship', fn (Builder $internshipQuery) => $internshipQuery->where('status', 'active')));

        $activeStudentCount = User::query()->where('role', UserRole::Student)->where('is_active', true)->count();
        $activeLecturerCount = User::query()->where('role', UserRole::Lecturer)->where('is_active', true)->count();
        $studentsWithoutSupervisorCount = User::query()
            ->where('role', UserRole::Student)
            ->where('is_active', true)
            ->whereDoesntHave('student.supervisorAssignments', fn ($query) => $query->where('status', 'active'))
            ->count();
        $activeSupervisorAssignmentCount = SupervisorAssignment::query()->where('status', 'active')->count();
        $submittedPeriodCount = (clone $currentPeriods)->whereHas('weeklyReport.firstVersion')->count();
        $onTimeSubmittedPeriodCount = (clone $currentPeriods)
            ->whereHas('weeklyReport.firstVersion', fn (Builder $query) => $query->where('is_late', false))
            ->count();
        $onTimeSubmissionRate = $submittedPeriodCount > 0
            ? (int) round(($onTimeSubmittedPeriodCount / $submittedPeriodCount) * 100)
            : 0;
        $onTimeSubmissionRateColor = match (true) {
            $submittedPeriodCount === 0 => 'gray',
            $onTimeSubmissionRate >= 90 => 'success',
            $onTimeSubmissionRate >= 70 => 'warning',
            default => 'danger',
        };
        $reportStatusCounts = (clone $reports)
            ->selectRaw('review_status, COUNT(*) as aggregate')
            ->groupBy('review_status')
            ->pluck('aggregate', 'review_status');
        $lateReportCount = (clone $currentPeriods)
            ->whereHas('weeklyReport.latestVersion', fn (Builder $query) => $query->where('is_late', true))
            ->count();
        $pendingReviewCount = (int) $reportStatusCounts->get(ReviewStatus::PendingReview->value, 0);
        $draftAssessmentCount = Assessment::query()
            ->where('status', AssessmentStatus::Draft)
            ->whereHas('weeklyReport.reportPeriod', fn (Builder $query) => $query
                ->whereDate('starts_on', '<=', today())
                ->whereDate('ends_on', '>=', today())
                ->whereHas('internship', fn (Builder $internshipQuery) => $internshipQuery->where('status', 'active')))
            ->count();

        $reportStatuses = collect(ReviewStatus::cases())
            ->map(fn (ReviewStatus $status): array => [
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => (int) $reportStatusCounts->get($status->value, 0),
            ])
            ->all();

        return [
            'primaryStats' => [
                [
                    'label' => 'Mahasiswa aktif',
                    'value' => $activeStudentCount,
                    'description' => 'Akun mahasiswa yang aktif',
                    'color' => 'info',
                ],
                [
                    'label' => 'Tingkat pengumpulan tepat waktu',
                    'value' => $onTimeSubmissionRate.'%',
                    'description' => $submittedPeriodCount > 0
                        ? number_format($onTimeSubmittedPeriodCount).' dari '.number_format($submittedPeriodCount).' laporan terkumpul'
                        : 'Belum ada laporan terkumpul pada periode aktif',
                    'color' => $onTimeSubmissionRateColor,
                ],
                [
                    'label' => 'Laporan menunggu pemeriksaan',
                    'value' => $pendingReviewCount,
                    'description' => 'Laporan pada periode aktif',
                    'color' => $pendingReviewCount > 0 ? 'warning' : 'success',
                ],
                [
                    'label' => 'Mahasiswa tanpa pembimbing aktif',
                    'value' => $studentsWithoutSupervisorCount,
                    'description' => $studentsWithoutSupervisorCount > 0 ? 'Perlu penugasan aktif' : 'Semua memiliki pembimbing',
                    'color' => $studentsWithoutSupervisorCount > 0 ? 'danger' : 'success',
                ],
            ],
            'secondaryStats' => [
                ['label' => 'Dosen aktif', 'value' => $activeLecturerCount, 'color' => 'info'],
                ['label' => 'Penugasan pembimbing aktif', 'value' => $activeSupervisorAssignmentCount, 'color' => 'success'],
                ['label' => 'Laporan terlambat', 'value' => $lateReportCount, 'color' => $lateReportCount > 0 ? 'warning' : 'success'],
                ['label' => 'Penilaian draf', 'value' => $draftAssessmentCount, 'color' => $draftAssessmentCount > 0 ? 'warning' : 'success'],
            ],
            'reportStatuses' => $reportStatuses,
            'recapUrl' => route('filament.admin.pages.administrative-recap'),
        ];
    }
}
