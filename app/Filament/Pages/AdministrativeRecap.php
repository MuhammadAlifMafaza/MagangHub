<?php

namespace App\Filament\Pages;

use App\Enums\AssessmentStatus;
use App\Enums\ReportCollectionStatus;
use App\Enums\ReviewStatus;
use App\Enums\SupervisorAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSlot;
use App\Models\Lecturer;
use App\Models\ReportPeriod;
use App\Models\ReportVersion;
use App\Models\Student;
use App\Models\WeeklyReport;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdministrativeRecap extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.administrative-recap';

    protected static ?string $navigationLabel = 'Rekap';

    protected static ?string $title = 'Rekap Administratif';

    protected static string|\UnitEnum|null $navigationGroup = 'Pemantauan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 20;

    public string $studentFilter = '';

    public string $lecturerFilter = '';

    public string $monthFilter = '';

    public string $yearFilter = '';

    public string $weekFilter = '';

    public string $reviewStatusFilter = '';

    public string $assessmentStatusFilter = '';

    public string $reportScope = 'attention';

    public static function canAccess(): bool
    {
        return Auth::user()?->role === UserRole::Admin;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['studentFilter', 'lecturerFilter', 'monthFilter', 'yearFilter'], true)) {
            $this->weekFilter = '';
        }

        if (in_array($property, ['studentFilter', 'lecturerFilter', 'monthFilter', 'yearFilter', 'weekFilter', 'reviewStatusFilter', 'assessmentStatusFilter', 'reportScope'], true)) {
            $this->resetPage();
        }
    }

    public function updatedReviewStatusFilter(string $status): void
    {
        if (ReviewStatus::tryFrom($status) !== null) {
            $this->reportScope = 'all';
            $this->assessmentStatusFilter = '';
        }
    }

    public function updatedAssessmentStatusFilter(string $status): void
    {
        if (AssessmentStatus::tryFrom($status) !== null) {
            $this->reportScope = 'all';
            $this->reviewStatusFilter = '';
        }
    }

    public function setReportScope(string $scope): void
    {
        if (! in_array($scope, ['attention', 'all', 'upcoming'], true)) {
            return;
        }

        $this->reportScope = $scope;
        $this->reviewStatusFilter = '';
        $this->assessmentStatusFilter = '';
        $this->resetPage();
    }

    public function setReportStatusShortcut(string $status): void
    {
        $reviewStatus = ReviewStatus::tryFrom($status);

        if ($reviewStatus === null) {
            return;
        }

        $this->reportScope = 'all';
        $this->reviewStatusFilter = $reviewStatus->value;
        $this->assessmentStatusFilter = '';
        $this->resetPage();
    }

    public function setAssessmentStatusShortcut(string $status): void
    {
        $assessmentStatus = AssessmentStatus::tryFrom($status);

        if ($assessmentStatus === null) {
            return;
        }

        $this->reportScope = 'all';
        $this->reviewStatusFilter = '';
        $this->assessmentStatusFilter = $assessmentStatus->value;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->studentFilter = '';
        $this->lecturerFilter = '';
        $this->monthFilter = '';
        $this->yearFilter = '';
        $this->weekFilter = '';
        $this->reviewStatusFilter = '';
        $this->assessmentStatusFilter = '';
        $this->reportScope = 'attention';

        $this->resetTableSearch();
        $this->resetTableColumnSearches();
        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->filteredPeriods())
            ->heading('Daftar laporan')
            ->description(fn (): string => $this->reportScopeDescription())
            ->emptyStateHeading('Tidak ada laporan pada pilihan ini')
            ->emptyStateDescription('Ubah kategori atau filter untuk melihat periode lainnya.')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->stackedOnMobile()
            ->columns([
                TextColumn::make('internship.student.user.name')
                    ->label('Mahasiswa')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(function (Builder $matches) use ($search): void {
                        $matches
                            ->whereHas('internship.student.user', fn (Builder $users): Builder => $users->whereLike('name', "%{$search}%"))
                            ->orWhereHas('internship.student', fn (Builder $students): Builder => $students->whereLike('nim', "%{$search}%"));
                    }))
                    ->weight('medium'),
                TextColumn::make('activeSupervisor')
                    ->label('Dosen pembimbing')
                    ->state(fn (ReportPeriod $record): string => $record->internship->student->supervisorAssignments->first()?->lecturer->user->name ?? '—'),
                TextColumn::make('week_number')
                    ->label('Minggu')
                    ->formatStateUsing(fn (int|string $state): string => 'Minggu ke-'.$state),
                TextColumn::make('starts_on')
                    ->label('Tanggal periode')
                    ->state(fn (ReportPeriod $record): string => $record->starts_on->format('d/m/Y').' – '.$record->ends_on->format('d/m/Y'))
                    ->sortable(),
                TextColumn::make('collectionStatus')
                    ->label('Pengumpulan')
                    ->state(fn (ReportPeriod $record): ReportCollectionStatus => $record->collectionStatus())
                    ->formatStateUsing(fn (ReportCollectionStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (ReportPeriod $record): string => $record->collectionStatus()->color()),
                TextColumn::make('reviewStatus')
                    ->label('Pemeriksaan')
                    ->state(fn (ReportPeriod $record): string => $record->weeklyReport?->review_status?->label() ?? '—')
                    ->badge()
                    ->color(fn (ReportPeriod $record): string => $record->weeklyReport?->review_status?->color() ?? 'gray'),
                TextColumn::make('assessmentStatus')
                    ->label('Penilaian')
                    ->state(fn (ReportPeriod $record): string => $record->weeklyReport?->assessment?->status?->label() ?? 'Belum dinilai')
                    ->badge()
                    ->color(fn (ReportPeriod $record): string => $record->weeklyReport?->assessment?->status?->color() ?? 'gray'),
            ])

            ->recordActions([
                Action::make('details')
                    ->label('Detail')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->url(fn (ReportPeriod $record): ?string => $record->weeklyReport === null
                        ? null
                        : ReportDetails::getUrl(['report' => $record->weeklyReport], panel: 'admin'))
                    ->visible(fn (ReportPeriod $record): bool => $record->weeklyReport !== null),
                Action::make('downloadLatest')
                    ->label('Unduh')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->url(fn (ReportPeriod $record): ?string => $this->getLatestVersionDownloadUrl($record))
                    ->visible(fn (ReportPeriod $record): bool => $this->hasStoredLatestVersion($record)),
            ]);
    }

    protected function getViewData(): array
    {
        $filteredReportIds = $this->filteredWeeklyReports()->select('weekly_reports.id');
        $weekOptions = $this->applyReportPeriodFilters(ReportPeriod::query(), includeWeekFilter: false)
            ->select('week_number')
            ->distinct()
            ->orderBy('week_number')
            ->pluck('week_number');
        $years = ReportPeriod::query()
            ->select('starts_on')
            ->distinct()
            ->orderByDesc('starts_on')
            ->pluck('starts_on')
            ->map(fn (mixed $date): string => Carbon::parse($date)->format('Y'))
            ->unique()
            ->values();

        $reportStatusCounts = $this->filteredWeeklyReports()
            ->selectRaw('review_status, COUNT(*) as aggregate')
            ->groupBy('review_status')
            ->pluck('aggregate', 'review_status');

        $supervisorStatusCounts = WeeklyReport::query()
            ->join('report_periods', 'weekly_reports.report_period_id', '=', 'report_periods.id')
            ->join('internships', 'report_periods.internship_id', '=', 'internships.id')
            ->join('supervisor_assignments', function ($join): void {
                $join->on('supervisor_assignments.student_id', '=', 'internships.student_id')
                    ->where('supervisor_assignments.status', SupervisorAssignmentStatus::Active->value);
            })
            ->join('lecturers', 'supervisor_assignments.lecturer_id', '=', 'lecturers.id')
            ->join('users', 'lecturers.user_id', '=', 'users.id')
            ->selectRaw('supervisor_assignments.lecturer_id as lecturer_id, users.name as lecturer_name, weekly_reports.review_status as review_status, COUNT(*) as total')
            ->whereIn('weekly_reports.id', $filteredReportIds)
            ->groupBy('supervisor_assignments.lecturer_id', 'users.name', 'weekly_reports.review_status')
            ->orderBy('lecturer_name')
            ->get();

        $assessmentCounts = Assessment::query()
            ->select('lecturer_id')
            ->selectRaw('status, COUNT(*) as total')
            ->whereIn('weekly_report_id', $filteredReportIds)
            ->when($this->lecturerFilter !== '', fn (Builder $assessments): Builder => $assessments->where('lecturer_id', $this->lecturerFilter))
            ->groupBy('lecturer_id', 'status')
            ->with('lecturer.user')
            ->get();

        return [
            'students' => Student::query()
                ->select(['id', 'user_id', 'nim'])
                ->with('user:id,name')
                ->orderBy('id')
                ->get(),
            'lecturers' => Lecturer::query()
                ->select(['id', 'user_id'])
                ->with('user:id,name')
                ->whereHas('supervisorAssignments', fn (Builder $assignments): Builder => $assignments->where('status', SupervisorAssignmentStatus::Active->value))
                ->orderBy('id')
                ->get(),
            'months' => [
                '1' => 'Januari',
                '2' => 'Februari',
                '3' => 'Maret',
                '4' => 'April',
                '5' => 'Mei',
                '6' => 'Juni',
                '7' => 'Juli',
                '8' => 'Agustus',
                '9' => 'September',
                '10' => 'Oktober',
                '11' => 'November',
                '12' => 'Desember',
            ],
            'years' => $years,
            'weeks' => $weekOptions,
            'pendingReviewCount' => (int) $reportStatusCounts->get(ReviewStatus::PendingReview->value, 0),
            'revisionRequestedCount' => (int) $reportStatusCounts->get(ReviewStatus::RevisionRequested->value, 0),
            'approvedReviewCount' => (int) $reportStatusCounts->get(ReviewStatus::Approved->value, 0),
            'publishedAssessmentCount' => (int) $assessmentCounts
                ->filter(fn (Assessment $assessment): bool => $assessment->status === AssessmentStatus::Published)
                ->sum('total'),
            'guidanceCounts' => $this->guidanceCounts(),
            'supervisorStatusCounts' => $supervisorStatusCounts,
            'assessmentCounts' => $assessmentCounts,
        ];
    }

    /** @return Builder<ReportPeriod> */
    private function filteredPeriods(): Builder
    {
        $query = $this->applyReportPeriodFilters(
            ReportPeriod::query()->with([
                'internship.student.user',
                'internship.student.supervisorAssignments' => fn (HasMany $query): HasMany => $query
                    ->where('status', SupervisorAssignmentStatus::Active->value)
                    ->with('lecturer.user'),
                'weeklyReport.firstVersion',
                'weeklyReport.latestVersion',
                'weeklyReport.assessment',
            ]),
        );

        if (($reviewStatus = ReviewStatus::tryFrom($this->reviewStatusFilter)) !== null) {
            $query->whereHas('weeklyReport', fn (Builder $reports): Builder => $reports->where('review_status', $reviewStatus->value));
        }

        if (($assessmentStatus = AssessmentStatus::tryFrom($this->assessmentStatusFilter)) !== null) {
            $query->whereHas('weeklyReport.assessment', fn (Builder $assessments): Builder => $assessments->where('status', $assessmentStatus->value));
        }

        $scope = in_array($this->reportScope, ['attention', 'all', 'upcoming'], true)
            ? $this->reportScope
            : 'attention';

        if ($scope === 'upcoming') {
            return $query
                ->whereDate('starts_on', '>', today(config('magang.timezone')))
                ->orderBy('starts_on')
                ->orderBy('id');
        }

        if ($scope === 'attention') {
            $now = now(config('magang.timezone'));

            return $query
                ->where(function (Builder $periods) use ($now): void {
                    $periods->whereHas('weeklyReport', fn (Builder $reports): Builder => $reports->whereIn('review_status', [
                        ReviewStatus::PendingReview->value,
                        ReviewStatus::RevisionRequested->value,
                    ]))->orWhere(function (Builder $overduePeriods) use ($now): void {
                        $overduePeriods
                            ->whereDate('starts_on', '<=', today(config('magang.timezone')))
                            ->where('due_at', '<', $now)
                            ->whereDoesntHave('weeklyReport.versions');
                    });
                })
                ->orderByRaw(
                    'CASE WHEN EXISTS (SELECT 1 FROM weekly_reports AS pending_reports WHERE pending_reports.report_period_id = report_periods.id AND pending_reports.review_status = ?) THEN 0 WHEN report_periods.due_at < ? AND NOT EXISTS (SELECT 1 FROM weekly_reports AS submitted_reports INNER JOIN report_versions AS submitted_versions ON submitted_versions.weekly_report_id = submitted_reports.id WHERE submitted_reports.report_period_id = report_periods.id) THEN 1 WHEN EXISTS (SELECT 1 FROM weekly_reports AS revision_reports WHERE revision_reports.report_period_id = report_periods.id AND revision_reports.review_status = ?) THEN 2 ELSE 3 END',
                    [ReviewStatus::PendingReview->value, $now, ReviewStatus::RevisionRequested->value],
                )
                ->orderBy('due_at')
                ->orderByDesc('id');
        }

        return $query->orderByDesc('starts_on')->orderByDesc('id');
    }

    /** @return Builder<WeeklyReport> */
    private function filteredWeeklyReports(): Builder
    {
        return WeeklyReport::query()->whereHas('reportPeriod', fn (Builder $periods): Builder => $this->applyReportPeriodFilters($periods));
    }

    /** @param Builder<ReportPeriod> $query
     * @return Builder<ReportPeriod>
     */
    private function applyReportPeriodFilters(Builder $query, bool $includeWeekFilter = true): Builder
    {
        return $query
            ->when($this->studentFilter !== '', fn (Builder $periods): Builder => $periods->whereHas('internship', fn (Builder $internships): Builder => $internships->where('student_id', $this->studentFilter)))
            ->when($this->lecturerFilter !== '', fn (Builder $periods): Builder => $periods->whereHas('internship.student.supervisorAssignments', fn (Builder $assignments): Builder => $assignments
                ->where('lecturer_id', $this->lecturerFilter)
                ->where('status', SupervisorAssignmentStatus::Active->value)))
            ->when($this->monthFilterValue() !== null, fn (Builder $periods): Builder => $periods->whereMonth('report_periods.starts_on', $this->monthFilterValue()))
            ->when($this->yearFilterValue() !== null, fn (Builder $periods): Builder => $periods->whereYear('report_periods.starts_on', $this->yearFilterValue()))
            ->when($includeWeekFilter && $this->weekFilter !== '', fn (Builder $periods): Builder => $periods->where('report_periods.week_number', $this->weekFilter));
    }

    private function reportScopeDescription(): string
    {
        return match ($this->reportScope) {
            'all' => 'Seluruh periode sesuai filter rekap. Gunakan pencarian dan pagination untuk menelusuri data.',
            'upcoming' => 'Periode yang belum dimulai, sesuai filter rekap.',
            default => 'Laporan menunggu pemeriksaan, perlu revisi, dan pengumpulan yang sudah melewati batas.',
        };
    }

    private function monthFilterValue(): ?int
    {
        $month = filter_var($this->monthFilter, FILTER_VALIDATE_INT);

        return is_int($month) && $month >= 1 && $month <= 12 ? $month : null;
    }

    private function yearFilterValue(): ?int
    {
        return preg_match('/^\d{4}$/', $this->yearFilter) === 1
            ? (int) $this->yearFilter
            : null;
    }

    /**
     * @return array<int, array{lecturer: Lecturer, slots: int, booked: int, completed: int, no_show: int}>
     */
    private function guidanceCounts(): array
    {
        $slotsQuery = GuidanceSlot::query()
            ->select('guidance_slots.lecturer_id')
            ->selectRaw('COUNT(*) as slot_count')
            ->when($this->lecturerFilter !== '', fn (Builder $query): Builder => $query->where('guidance_slots.lecturer_id', $this->lecturerFilter));
        $this->constrainToMatchingPeriods($slotsQuery);

        $slots = $slotsQuery
            ->groupBy('lecturer_id')
            ->get()
            ->keyBy('lecturer_id');

        $bookingsQuery = GuidanceBooking::query()
            ->join('guidance_slots', 'guidance_bookings.guidance_slot_id', '=', 'guidance_slots.id')
            ->selectRaw('guidance_slots.lecturer_id as lecturer_id')
            ->selectRaw("SUM(CASE WHEN guidance_bookings.status = 'booked' THEN 1 ELSE 0 END) as booked_count")
            ->selectRaw("SUM(CASE WHEN guidance_bookings.status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            ->selectRaw("SUM(CASE WHEN guidance_bookings.status = 'no_show' THEN 1 ELSE 0 END) as no_show_count")
            ->when($this->lecturerFilter !== '', fn (Builder $query): Builder => $query->where('guidance_slots.lecturer_id', $this->lecturerFilter))
            ->when($this->studentFilter !== '', fn (Builder $query): Builder => $query->where('guidance_bookings.student_id', $this->studentFilter));
        $this->constrainToMatchingPeriods($bookingsQuery, matchBookingStudent: true);

        $bookings = $bookingsQuery
            ->groupBy('guidance_slots.lecturer_id')
            ->get()
            ->keyBy('lecturer_id');

        return Lecturer::query()
            ->with('user')
            ->whereHas('supervisorAssignments', fn (Builder $assignments): Builder => $assignments->where('status', SupervisorAssignmentStatus::Active->value))
            ->when($this->lecturerFilter !== '', fn (Builder $query): Builder => $query->whereKey($this->lecturerFilter))
            ->when($this->studentFilter !== '', fn (Builder $query): Builder => $query->whereHas('supervisorAssignments', fn (Builder $assignments): Builder => $assignments
                ->where('student_id', $this->studentFilter)
                ->where('status', SupervisorAssignmentStatus::Active->value)))
            ->orderBy('id')
            ->get()
            ->map(fn (Lecturer $lecturer): array => [
                'lecturer' => $lecturer,
                'slots' => (int) ($slots->get($lecturer->id)->slot_count ?? 0),
                'booked' => (int) ($bookings->get($lecturer->id)->booked_count ?? 0),
                'completed' => (int) ($bookings->get($lecturer->id)->completed_count ?? 0),
                'no_show' => (int) ($bookings->get($lecturer->id)->no_show_count ?? 0),
            ])
            ->all();
    }

    /** @param Builder<GuidanceSlot>|Builder<GuidanceBooking> $query
     * @return Builder<GuidanceSlot>|Builder<GuidanceBooking>
     */
    private function constrainToMatchingPeriods(Builder $query, bool $matchBookingStudent = false): Builder
    {
        $matchingPeriods = ReportPeriod::query()
            ->join('internships', 'report_periods.internship_id', '=', 'internships.id')
            ->join('supervisor_assignments', function (JoinClause $join): void {
                $join->on('supervisor_assignments.student_id', '=', 'internships.student_id')
                    ->on('supervisor_assignments.lecturer_id', '=', 'guidance_slots.lecturer_id')
                    ->where('supervisor_assignments.status', SupervisorAssignmentStatus::Active->value);
            })
            ->select('report_periods.id')
            ->whereRaw('DATE(guidance_slots.starts_at) BETWEEN report_periods.starts_on AND report_periods.ends_on');

        if ($matchBookingStudent) {
            $matchingPeriods->whereColumn('internships.student_id', 'guidance_bookings.student_id');
        }

        $this->applyReportPeriodFilters($matchingPeriods);

        return $query->whereExists($matchingPeriods->toBase());
    }

    private function hasStoredLatestVersion(ReportPeriod $period): bool
    {
        $version = $period->weeklyReport?->latestVersion;

        if (! $version instanceof ReportVersion) {
            return false;
        }

        $disk = Storage::disk($version->disk);

        return $disk->exists($version->stored_path) && $disk->size($version->stored_path) === $version->size_bytes;
    }

    private function getLatestVersionDownloadUrl(ReportPeriod $period): ?string
    {
        $version = $period->weeklyReport?->latestVersion;

        if (! $version instanceof ReportVersion || ! $this->hasStoredLatestVersion($period)) {
            return null;
        }

        return route('laporan.versi.download', $version);
    }
}
