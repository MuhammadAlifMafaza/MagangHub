<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSlot;
use App\Models\Lecturer;
use App\Models\ReportPeriod;
use App\Models\Student;
use App\Models\WeeklyReport;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class AdministrativeRecap extends Page
{
    use WithPagination;

    protected string $view = 'filament.pages.administrative-recap';

    protected static ?string $navigationLabel = 'Rekap';

    protected static ?string $title = 'Rekap Administratif';

    protected static string|\UnitEnum|null $navigationGroup = 'Pemantauan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 50;

    public string $studentFilter = '';

    public string $lecturerFilter = '';

    public string $weekFilter = '';

    public string $reviewStatusFilter = '';

    public static function canAccess(): bool
    {
        return Auth::user()?->role === UserRole::Admin;
    }

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    protected function getViewData(): array
    {
        $filteredPeriods = $this->filteredPeriods();
        $filteredReportIds = $this->filteredWeeklyReports()->select('weekly_reports.id');

        return [
            'students' => Student::query()->with('user')->orderBy('id')->get(),
            'lecturers' => Lecturer::query()->with('user')->orderBy('id')->get(),
            'weeks' => ReportPeriod::query()->select('week_number')->distinct()->orderBy('week_number')->pluck('week_number'),
            'periods' => $filteredPeriods->paginate(15),
            'guidanceCounts' => $this->guidanceCounts(),
            'reviewCounts' => WeeklyReport::query()
                ->join('report_periods', 'weekly_reports.report_period_id', '=', 'report_periods.id')
                ->join('internships', 'report_periods.internship_id', '=', 'internships.id')
                ->join('supervisor_assignments', function ($join): void {
                    $join->on('supervisor_assignments.student_id', '=', 'internships.student_id')
                        ->where('supervisor_assignments.status', 'active');
                })
                ->join('lecturers', 'supervisor_assignments.lecturer_id', '=', 'lecturers.id')
                ->join('users', 'lecturers.user_id', '=', 'users.id')
                ->selectRaw('supervisor_assignments.lecturer_id as lecturer_id, users.name as lecturer_name, weekly_reports.review_status as review_status, COUNT(*) as total')
                ->whereIn('weekly_reports.id', $filteredReportIds)
                ->when($this->lecturerFilter !== '', fn (Builder $query) => $query->where('supervisor_assignments.lecturer_id', $this->lecturerFilter))
                ->groupBy('supervisor_assignments.lecturer_id', 'users.name', 'weekly_reports.review_status')
                ->get(),
            'assessmentCounts' => Assessment::query()
                ->select('lecturer_id')
                ->selectRaw('status, COUNT(*) as total')
                ->whereIn('weekly_report_id', $filteredReportIds)
                ->when($this->lecturerFilter !== '', fn (Builder $query) => $query->where('lecturer_id', $this->lecturerFilter))
                ->groupBy('lecturer_id', 'status')
                ->with('lecturer.user')
                ->get(),
        ];
    }

    private function filteredPeriods(): Builder
    {
        return ReportPeriod::query()
            ->with(['internship.student.user', 'internship.student.supervisorAssignments.lecturer.user', 'weeklyReport.latestVersion', 'weeklyReport.assessment'])
            ->when($this->studentFilter !== '', fn (Builder $query) => $query->whereHas('internship', fn (Builder $internshipQuery) => $internshipQuery->where('student_id', $this->studentFilter)))
            ->when($this->lecturerFilter !== '', fn (Builder $query) => $query->whereHas('internship.student.supervisorAssignments', fn (Builder $assignmentQuery) => $assignmentQuery->where('lecturer_id', $this->lecturerFilter)))
            ->when($this->weekFilter !== '', fn (Builder $query) => $query->where('week_number', $this->weekFilter))
            ->when($this->reviewStatusFilter !== '', fn (Builder $query) => $query->whereHas('weeklyReport', fn (Builder $reportQuery) => $reportQuery->where('review_status', $this->reviewStatusFilter)))
            ->orderByDesc('starts_on');
    }

    private function filteredWeeklyReports(): Builder
    {
        return WeeklyReport::query()
            ->whereHas('reportPeriod', fn (Builder $query) => $query
                ->when($this->studentFilter !== '', fn (Builder $periodQuery) => $periodQuery->whereHas('internship', fn (Builder $internshipQuery) => $internshipQuery->where('student_id', $this->studentFilter)))
                ->when($this->lecturerFilter !== '', fn (Builder $periodQuery) => $periodQuery->whereHas('internship.student.supervisorAssignments', fn (Builder $assignmentQuery) => $assignmentQuery->where('lecturer_id', $this->lecturerFilter)))
                ->when($this->weekFilter !== '', fn (Builder $periodQuery) => $periodQuery->where('week_number', $this->weekFilter)))
            ->when($this->reviewStatusFilter !== '', fn (Builder $query) => $query->where('review_status', $this->reviewStatusFilter));
    }

    private function guidanceCounts(): array
    {
        $periodBounds = null;
        if ($this->weekFilter !== '') {
            $periodBounds = ReportPeriod::query()
                ->where('week_number', $this->weekFilter)
                ->when($this->studentFilter !== '', fn (Builder $query) => $query->whereHas('internship', fn (Builder $internshipQuery) => $internshipQuery->where('student_id', $this->studentFilter)))
                ->selectRaw('MIN(starts_on) as earliest_start, MAX(ends_on) as latest_end')
                ->first();

            if (! $periodBounds?->earliest_start || ! $periodBounds?->latest_end) {
                return [];
            }
        }

        $slots = GuidanceSlot::query()
            ->select('lecturer_id')
            ->selectRaw('COUNT(*) as slot_count')
            ->when($this->lecturerFilter !== '', fn (Builder $query) => $query->where('lecturer_id', $this->lecturerFilter))
            ->when($this->studentFilter !== '', fn (Builder $query) => $query->whereHas('lecturer.supervisorAssignments', fn (Builder $assignmentQuery) => $assignmentQuery
                ->where('student_id', $this->studentFilter)->where('status', 'active')))
            ->when($periodBounds?->earliest_start && $periodBounds?->latest_end, fn (Builder $query) => $query->whereBetween('starts_at', [$periodBounds->earliest_start, $periodBounds->latest_end]))
            ->groupBy('lecturer_id')
            ->get()
            ->keyBy('lecturer_id');

        $bookings = GuidanceBooking::query()
            ->join('guidance_slots', 'guidance_bookings.guidance_slot_id', '=', 'guidance_slots.id')
            ->selectRaw('guidance_slots.lecturer_id as lecturer_id')
            ->selectRaw("SUM(CASE WHEN guidance_bookings.status = 'booked' THEN 1 ELSE 0 END) as booked_count")
            ->selectRaw("SUM(CASE WHEN guidance_bookings.status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            ->selectRaw("SUM(CASE WHEN guidance_bookings.status = 'no_show' THEN 1 ELSE 0 END) as no_show_count")
            ->when($this->lecturerFilter !== '', fn (Builder $query) => $query->where('guidance_slots.lecturer_id', $this->lecturerFilter))
            ->when($this->studentFilter !== '', fn (Builder $query) => $query->where('guidance_bookings.student_id', $this->studentFilter))
            ->when($periodBounds?->earliest_start && $periodBounds?->latest_end, fn (Builder $query) => $query->whereBetween('guidance_slots.starts_at', [$periodBounds->earliest_start, $periodBounds->latest_end]))
            ->groupBy('guidance_slots.lecturer_id')
            ->get()
            ->keyBy('lecturer_id');

        return Lecturer::query()
            ->with('user')
            ->when($this->lecturerFilter !== '', fn (Builder $query) => $query->whereKey($this->lecturerFilter))
            ->when($this->studentFilter !== '', fn (Builder $query) => $query->whereHas('supervisorAssignments', fn (Builder $assignmentQuery) => $assignmentQuery
                ->where('student_id', $this->studentFilter)->where('status', 'active')))
            ->orderBy('id')
            ->get()
            ->map(fn (Lecturer $lecturer): array => [
                'lecturer' => $lecturer,
                'slots' => (int) ($slots->get($lecturer->id)?->slot_count ?? 0),
                'booked' => (int) ($bookings->get($lecturer->id)?->booked_count ?? 0),
                'completed' => (int) ($bookings->get($lecturer->id)?->completed_count ?? 0),
                'no_show' => (int) ($bookings->get($lecturer->id)?->no_show_count ?? 0),
            ])
            ->all();
    }
}
