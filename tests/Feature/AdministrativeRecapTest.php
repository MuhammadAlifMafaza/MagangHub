<?php

use App\Enums\AssessmentStatus;
use App\Enums\GuidanceBookingStatus;
use App\Enums\ReportReviewDecision;
use App\Enums\ReviewStatus;
use App\Enums\SupervisorAssignmentStatus;
use App\Enums\UserRole;
use App\Filament\Dosen\Widgets\LecturerGuidanceStats;
use App\Filament\Dosen\Widgets\PendingLecturerReports;
use App\Filament\Pages\AdministrativeRecap;
use App\Filament\Pages\ReportDetails as ReportDetailsPage;
use App\Filament\Widgets\AdminOverviewStats;
use App\Models\Assessment;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSlot;
use App\Models\Internship;
use App\Models\Lecturer;
use App\Models\ReportPeriod;
use App\Models\ReportReview;
use App\Models\ReportVersion;
use App\Models\Student;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

function makeRecapReport(Student $student, int $week, ReviewStatus $status = ReviewStatus::PendingReview): WeeklyReport
{
    $internship = Internship::factory()->create(['student_id' => $student->id]);
    $period = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => $week,
        'starts_on' => now()->subDays(7),
        'ends_on' => now()->subDay(),
        'due_at' => now()->subDay(),
    ]);

    return WeeklyReport::factory()->create(['report_period_id' => $period->id, 'review_status' => $status]);
}

function administrativeRecapReportTableHtml(string $html): string
{
    $labelPosition = strpos($html, 'aria-label="Daftar laporan"');
    $tableStart = strrpos(substr($html, 0, $labelPosition), '<table');
    $tableEnd = strpos($html, '</table>', $labelPosition) + strlen('</table>');

    return substr($html, $tableStart, $tableEnd - $tableStart);
}

function administrativeRecapGuidanceRowHtml(string $html, string $lecturerName): string
{
    $sectionPosition = strpos($html, 'Pembimbingan per dosen');
    $tableStart = strpos($html, '<table', $sectionPosition);
    $tableEnd = strpos($html, '</table>', $tableStart) + strlen('</table>');
    $table = substr($html, $tableStart, $tableEnd - $tableStart);
    $lecturerPosition = strpos($table, $lecturerName);
    $rowStart = strrpos(substr($table, 0, $lecturerPosition), '<tr');
    $rowEnd = strpos($table, '</tr>', $lecturerPosition) + strlen('</tr>');

    return substr($table, $rowStart, $rowEnd - $rowStart);
}

test('administrative recap is restricted to admins and is read-only', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $lecturer = User::factory()->create(['role' => UserRole::Lecturer]);

    $this->actingAs($lecturer)->get('/admin/administrative-recap')->assertForbidden();
    $this->actingAs($admin)->get('/admin/administrative-recap')->assertOk()
        ->assertSee('Daftar laporan')
        ->assertSee('Pembimbingan per dosen')
        ->assertSee('Status laporan dan penilaian per dosen')
        ->assertDontSee('type="submit"');

    $report = WeeklyReport::factory()->create();
    $assessment = Assessment::factory()->create(['weekly_report_id' => $report->id]);

    expect(Gate::forUser($admin)->denies('review', $report))->toBeTrue()
        ->and(Gate::forUser($admin)->denies('assess', $report))->toBeTrue()
        ->and(Gate::forUser($admin)->denies('update', $assessment))->toBeTrue();
});

test('administrative recap filters collection and lecturer aggregates by student and week', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create(['user_id' => User::factory()->create(['name' => 'Filtered Student'])->id]);
    $otherStudent = Student::factory()->create(['user_id' => User::factory()->create(['name' => 'Other Student'])->id]);
    $lecturer = Lecturer::factory()->create();
    SupervisorAssignment::factory()->create(['student_id' => $student->id, 'lecturer_id' => $lecturer->id]);
    $weeklyReport = makeRecapReport($student, 4, ReviewStatus::Approved);
    $otherReport = makeRecapReport($otherStudent, 5);
    $version = ReportVersion::factory()->create([
        'weekly_report_id' => $weeklyReport->id,
        'uploaded_by' => $student->user_id,
    ]);
    ReportReview::factory()->create([
        'weekly_report_id' => $weeklyReport->id,
        'report_version_id' => $version->id,
        'lecturer_id' => $lecturer->id,
        'decision' => ReportReviewDecision::Approved,
    ]);
    Assessment::factory()->create([
        'weekly_report_id' => $weeklyReport->id,
        'lecturer_id' => $lecturer->id,
        'status' => AssessmentStatus::Published,
    ]);
    foreach ([GuidanceBookingStatus::Booked, GuidanceBookingStatus::Completed, GuidanceBookingStatus::NoShow] as $index => $bookingStatus) {
        $slot = GuidanceSlot::factory()->create([
            'lecturer_id' => $lecturer->id,
            'starts_at' => now()->subDays(4)->addHours($index),
        ]);
        GuidanceBooking::factory()->create([
            'guidance_slot_id' => $slot->id,
            'student_id' => $student->id,
            'status' => $bookingStatus,
        ]);
    }

    $this->actingAs($admin);

    $filteredComponent = Livewire::test(AdministrativeRecap::class)
        ->set('studentFilter', (string) $student->id)
        ->set('lecturerFilter', (string) $lecturer->id)
        ->set('weekFilter', '4')
        ->set('reviewStatusFilter', ReviewStatus::Approved->value);

    $filteredTable = administrativeRecapReportTableHtml($filteredComponent->html());

    expect($filteredTable)->toContain('Filtered Student')
        ->and($filteredTable)->not->toContain('Other Student');

    $filteredComponent
        ->assertSee(ReportDetailsPage::getUrl(['report' => $weeklyReport], panel: 'admin'))
        ->assertSee('Dipublikasikan')
        ->assertSee('>3</td>', false);

    $otherWeekComponent = Livewire::test(AdministrativeRecap::class)->set('weekFilter', '5');
    $otherWeekTable = administrativeRecapReportTableHtml($otherWeekComponent->html());

    expect($otherWeekTable)->toContain('Other Student')
        ->and($otherWeekTable)->not->toContain('Filtered Student');
});

test('administrative recap filters periods by their start month and year across duplicate week numbers', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $lecturer = Lecturer::factory()->create(['user_id' => User::factory()->create(['name' => 'Period Filter Supervisor'])->id]);
    $otherLecturer = Lecturer::factory()->create(['user_id' => User::factory()->create(['name' => 'Other Assessment Lecturer'])->id]);
    $weeklyReports = [];
    $createPeriodReport = function (string $studentName, string $startsOn, string $endsOn, int $weekNumber = 1) use ($lecturer, &$weeklyReports): void {
        $student = Student::factory()->create([
            'user_id' => User::factory()->create(['name' => $studentName])->id,
        ]);
        SupervisorAssignment::factory()->create([
            'student_id' => $student->id,
            'lecturer_id' => $lecturer->id,
        ]);
        $internship = Internship::factory()->create(['student_id' => $student->id]);
        $period = ReportPeriod::factory()->create([
            'internship_id' => $internship->id,
            'week_number' => $weekNumber,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'due_at' => $endsOn.' 23:59:59',
        ]);

        $weeklyReports[$studentName] = WeeklyReport::factory()->create([
            'report_period_id' => $period->id,
            'review_status' => ReviewStatus::PendingReview,
        ]);
    };

    $createPeriodReport('January 2025 Student', '2025-01-31', '2025-02-06');
    $createPeriodReport('January 2026 Student', '2026-01-15', '2026-01-21');
    $createPeriodReport('February 2025 Student', '2025-02-01', '2025-02-07', 2);
    Assessment::factory()->create([
        'weekly_report_id' => $weeklyReports['January 2025 Student']->id,
        'lecturer_id' => $lecturer->id,
        'status' => AssessmentStatus::Published,
    ]);
    Assessment::factory()->create([
        'weekly_report_id' => $weeklyReports['January 2026 Student']->id,
        'lecturer_id' => $otherLecturer->id,
        'status' => AssessmentStatus::Published,
    ]);
    Assessment::factory()->create([
        'weekly_report_id' => $weeklyReports['February 2025 Student']->id,
        'lecturer_id' => $lecturer->id,
        'status' => AssessmentStatus::Published,
    ]);

    $this->actingAs($admin);

    $component = Livewire::test(AdministrativeRecap::class)
        ->call('setReportScope', 'all')
        ->set('lecturerFilter', (string) $lecturer->id)
        ->set('monthFilter', '1')
        ->set('weekFilter', '1');
    $januaryTable = administrativeRecapReportTableHtml($component->html());
    $januaryHtml = $component->html();
    $pendingCardPosition = strpos($januaryHtml, 'Menunggu pemeriksaan');
    $pendingCardEnd = strpos($januaryHtml, 'Lihat laporan', $pendingCardPosition);
    $pendingCard = substr($januaryHtml, $pendingCardPosition, $pendingCardEnd - $pendingCardPosition);
    $publishedCardPosition = strpos($januaryHtml, 'Nilai dipublikasikan');
    $publishedCardEnd = strpos($januaryHtml, 'Lihat laporan disetujui', $publishedCardPosition);
    $publishedCard = substr($januaryHtml, $publishedCardPosition, $publishedCardEnd - $publishedCardPosition);
    $januarySummaryStart = strpos($januaryHtml, 'id="recap-supervisor-status-heading"');
    $januarySummaryEnd = strpos($januaryHtml, 'id="recap-assessment-heading"', $januarySummaryStart);
    $januarySupervisorSummary = substr($januaryHtml, $januarySummaryStart, $januarySummaryEnd - $januarySummaryStart);

    expect($januaryTable)->toContain('January 2025 Student')
        ->and($januaryTable)->toContain('January 2026 Student')
        ->and($januaryTable)->not->toContain('February 2025 Student')
        ->and($januaryTable)->toContain('31/01/2025 – 06/02/2025')
        ->and($pendingCard)->toContain('>2</span>')
        ->and($januarySupervisorSummary)->toContain('>2</td>')
        ->and($publishedCard)->toContain('>1</span>')
        ->and($januaryHtml)->not->toContain('Other Assessment Lecturer');

    $component->set('yearFilter', '2025')->assertSet('weekFilter', '');
    $component->set('weekFilter', '1');
    $yearFilteredHtml = $component->html();
    $yearFilteredTable = administrativeRecapReportTableHtml($yearFilteredHtml);
    $yearPendingCardPosition = strpos($yearFilteredHtml, 'Menunggu pemeriksaan');
    $yearPendingCardEnd = strpos($yearFilteredHtml, 'Lihat laporan', $yearPendingCardPosition);
    $yearPendingCard = substr($yearFilteredHtml, $yearPendingCardPosition, $yearPendingCardEnd - $yearPendingCardPosition);
    $yearSummaryStart = strpos($yearFilteredHtml, 'id="recap-supervisor-status-heading"');
    $yearSummaryEnd = strpos($yearFilteredHtml, 'id="recap-assessment-heading"', $yearSummaryStart);
    $yearSupervisorSummary = substr($yearFilteredHtml, $yearSummaryStart, $yearSummaryEnd - $yearSummaryStart);

    expect($yearFilteredTable)->toContain('January 2025 Student')
        ->and($yearFilteredTable)->not->toContain('January 2026 Student')
        ->and($yearPendingCard)->toContain('>1</span>')
        ->and($yearSupervisorSummary)->toContain('>1</td>');

    $component->set('monthFilter', '2')->assertSet('weekFilter', '');
    $februaryHtml = $component->html();
    $weekSelectStart = strpos($februaryHtml, 'id="recap-week-filter"');
    $weekSelectEnd = strpos($februaryHtml, '</select>', $weekSelectStart);
    $weekSelect = substr($februaryHtml, $weekSelectStart, $weekSelectEnd - $weekSelectStart);
    $component->set('weekFilter', '2');
    $februaryHtml = $component->html();
    $februaryTable = administrativeRecapReportTableHtml($februaryHtml);
    $februaryPendingCardPosition = strpos($februaryHtml, 'Menunggu pemeriksaan');
    $februaryPendingCardEnd = strpos($februaryHtml, 'Lihat laporan', $februaryPendingCardPosition);
    $februaryPendingCard = substr($februaryHtml, $februaryPendingCardPosition, $februaryPendingCardEnd - $februaryPendingCardPosition);

    expect($weekSelect)->toContain('Minggu ke-2')
        ->and($weekSelect)->not->toContain('Minggu ke-1')
        ->and($februaryTable)->toContain('February 2025 Student')
        ->and($februaryTable)->not->toContain('January 2025 Student')
        ->and($februaryTable)->not->toContain('January 2026 Student')
        ->and($februaryPendingCard)->toContain('>1</span>');
});

test('administrative recap clears the selected week when a dependent recap filter changes', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin);

    Livewire::test(AdministrativeRecap::class)
        ->set('weekFilter', '4')
        ->set('studentFilter', '1')
        ->assertSet('weekFilter', '')
        ->set('weekFilter', '4')
        ->set('lecturerFilter', '1')
        ->assertSet('weekFilter', '')
        ->set('weekFilter', '4')
        ->set('monthFilter', '1')
        ->assertSet('weekFilter', '')
        ->set('weekFilter', '4')
        ->set('yearFilter', '2025')
        ->assertSet('weekFilter', '');
});

test('administrative recap clears table and recap filters when filters are reset', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin);

    Livewire::test(AdministrativeRecap::class)
        ->set('studentFilter', '1')
        ->set('lecturerFilter', '2')
        ->set('monthFilter', '3')
        ->set('yearFilter', '2025')
        ->set('weekFilter', '4')
        ->set('reviewStatusFilter', ReviewStatus::Approved->value)
        ->set('assessmentStatusFilter', AssessmentStatus::Published->value)
        ->set('reportScope', 'upcoming')
        ->set('tableSearch', 'search term')
        ->set('tableColumnSearches', ['internship.student.user.name' => 'student'])
        ->call('resetFilters')
        ->assertSet('studentFilter', '')
        ->assertSet('lecturerFilter', '')
        ->assertSet('monthFilter', '')
        ->assertSet('yearFilter', '')
        ->assertSet('weekFilter', '')
        ->assertSet('reviewStatusFilter', '')
        ->assertSet('assessmentStatusFilter', '')
        ->assertSet('reportScope', 'attention')
        ->assertSet('tableSearch', '')
        ->assertSet('tableColumnSearches', []);
});

test('administrative recap scopes attention, all periods, and upcoming periods', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $pendingStudent = Student::factory()->create(['user_id' => User::factory()->create(['name' => 'Pending Scope Student'])->id]);
    $revisionStudent = Student::factory()->create(['user_id' => User::factory()->create(['name' => 'Revision Scope Student'])->id]);
    $approvedStudent = Student::factory()->create(['user_id' => User::factory()->create(['name' => 'Approved Scope Student'])->id]);
    $missingStudent = Student::factory()->create(['user_id' => User::factory()->create(['name' => 'Overdue Scope Student'])->id]);
    $upcomingStudent = Student::factory()->create(['user_id' => User::factory()->create(['name' => 'Upcoming Scope Student'])->id]);

    makeRecapReport($pendingStudent, 1);
    makeRecapReport($revisionStudent, 2, ReviewStatus::RevisionRequested);
    $approvedReport = makeRecapReport($approvedStudent, 3, ReviewStatus::Approved);
    ReportVersion::factory()->create(['weekly_report_id' => $approvedReport->id, 'uploaded_by' => $approvedStudent->user_id]);

    $missingInternship = Internship::factory()->create(['student_id' => $missingStudent->id]);
    ReportPeriod::factory()->create([
        'internship_id' => $missingInternship->id,
        'week_number' => 4,
        'starts_on' => today()->subDays(7),
        'ends_on' => today()->subDay(),
        'due_at' => now()->subDay(),
    ]);

    $upcomingInternship = Internship::factory()->create(['student_id' => $upcomingStudent->id]);
    ReportPeriod::factory()->create([
        'internship_id' => $upcomingInternship->id,
        'week_number' => 5,
        'starts_on' => today()->addDay(),
        'ends_on' => today()->addDays(7),
        'due_at' => now()->addDays(7),
    ]);

    $this->actingAs($admin);

    $component = Livewire::test(AdministrativeRecap::class)->assertSet('reportScope', 'attention');
    $component->assertSee('Laporan menunggu pemeriksaan, perlu revisi, dan pengumpulan yang sudah melewati batas.');
    $attentionTable = administrativeRecapReportTableHtml($component->html());

    expect($attentionTable)->toContain('Pending Scope Student')
        ->and($attentionTable)->toContain('Revision Scope Student')
        ->and($attentionTable)->toContain('Overdue Scope Student')
        ->and($attentionTable)->not->toContain('Approved Scope Student')
        ->and($attentionTable)->not->toContain('Upcoming Scope Student');

    $component->call('setReportScope', 'all');
    $component->assertSee('Seluruh periode sesuai filter rekap. Gunakan pencarian dan pagination untuk menelusuri data.');
    $allPeriodsTable = administrativeRecapReportTableHtml($component->html());

    expect($allPeriodsTable)->toContain('Approved Scope Student')
        ->and($allPeriodsTable)->toContain('Upcoming Scope Student');

    $component->call('setReportScope', 'upcoming');
    $component->assertSee('Periode yang belum dimulai, sesuai filter rekap.');
    $upcomingTable = administrativeRecapReportTableHtml($component->html());

    expect($upcomingTable)->toContain('Upcoming Scope Student')
        ->and($upcomingTable)->not->toContain('Pending Scope Student')
        ->and($upcomingTable)->not->toContain('Overdue Scope Student');
});

test('administrative recap status shortcuts filter their matching reports', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $pendingStudent = Student::factory()->create(['user_id' => User::factory()->create(['name' => 'Shortcut Pending Student'])->id]);
    $revisionStudent = Student::factory()->create(['user_id' => User::factory()->create(['name' => 'Shortcut Revision Student'])->id]);
    $publishedStudent = Student::factory()->create(['user_id' => User::factory()->create(['name' => 'Shortcut Published Student'])->id]);
    $draftStudent = Student::factory()->create(['user_id' => User::factory()->create(['name' => 'Shortcut Draft Student'])->id]);

    makeRecapReport($pendingStudent, 1, ReviewStatus::PendingReview);
    makeRecapReport($revisionStudent, 2, ReviewStatus::RevisionRequested);
    $publishedReport = makeRecapReport($publishedStudent, 3, ReviewStatus::Approved);
    $draftReport = makeRecapReport($draftStudent, 4, ReviewStatus::Approved);
    $lecturer = Lecturer::factory()->create();
    Assessment::factory()->create([
        'weekly_report_id' => $publishedReport->id,
        'lecturer_id' => $lecturer->id,
        'status' => AssessmentStatus::Published,
    ]);
    Assessment::factory()->create([
        'weekly_report_id' => $draftReport->id,
        'lecturer_id' => $lecturer->id,
        'status' => AssessmentStatus::Draft,
    ]);

    $this->actingAs($admin);

    $component = Livewire::test(AdministrativeRecap::class)
        ->call('setReportStatusShortcut', ReviewStatus::RevisionRequested->value)
        ->assertSet('reportScope', 'all')
        ->assertSet('reviewStatusFilter', ReviewStatus::RevisionRequested->value);
    $revisionTable = administrativeRecapReportTableHtml($component->html());

    expect($revisionTable)->toContain('Shortcut Revision Student')
        ->and($revisionTable)->not->toContain('Shortcut Pending Student');

    $component
        ->call('setAssessmentStatusShortcut', AssessmentStatus::Published->value)
        ->assertSet('reviewStatusFilter', '')
        ->assertSet('assessmentStatusFilter', AssessmentStatus::Published->value)
        ->assertSee('Filter aktif: Nilai Dipublikasikan');
    $publishedTable = administrativeRecapReportTableHtml($component->html());

    expect($publishedTable)->toContain('Shortcut Published Student')
        ->and($publishedTable)->not->toContain('Shortcut Draft Student');
});

test('administrative recap supervisor status totals use only active assignments', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create();
    $formerLecturer = Lecturer::factory()->create(['user_id' => User::factory()->create(['name' => 'Former Supervisor'])->id]);
    $activeLecturer = Lecturer::factory()->create(['user_id' => User::factory()->create(['name' => 'Active Supervisor'])->id]);

    SupervisorAssignment::factory()->create([
        'student_id' => $student->id,
        'lecturer_id' => $formerLecturer->id,
        'status' => SupervisorAssignmentStatus::Ended,
        'ended_at' => now(),
    ]);
    SupervisorAssignment::factory()->create([
        'student_id' => $student->id,
        'lecturer_id' => $activeLecturer->id,
        'status' => SupervisorAssignmentStatus::Active,
    ]);
    makeRecapReport($student, 1);

    $this->actingAs($admin);

    $html = Livewire::test(AdministrativeRecap::class)->html();
    $summaryStart = strpos($html, 'id="recap-supervisor-status-heading"');
    $summaryEnd = strpos($html, 'id="recap-assessment-heading"');
    $summary = substr($html, $summaryStart, $summaryEnd - $summaryStart);

    expect($summaryStart)->not->toBeFalse()
        ->and($summaryEnd)->not->toBeFalse()
        ->and($summary)->toContain('Active Supervisor')
        ->and($summary)->not->toContain('Former Supervisor');
});

test('administrative recap includes guidance slots on the selected period end date', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create();
    $lecturer = Lecturer::factory()->create();
    SupervisorAssignment::factory()->create(['student_id' => $student->id, 'lecturer_id' => $lecturer->id]);
    $internship = Internship::factory()->create(['student_id' => $student->id]);
    $periodEnd = today();
    ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 6,
        'starts_on' => $periodEnd->copy()->subDays(6),
        'ends_on' => $periodEnd,
        'due_at' => now(),
    ]);
    GuidanceSlot::factory()->create([
        'lecturer_id' => $lecturer->id,
        'starts_at' => $periodEnd->copy()->setTime(23, 30),
        'ends_at' => $periodEnd->copy()->setTime(23, 59),
    ]);
    GuidanceSlot::factory()->create([
        'lecturer_id' => $lecturer->id,
        'starts_at' => $periodEnd->copy()->addDay()->setTime(0, 30),
        'ends_at' => $periodEnd->copy()->addDay()->setTime(1, 0),
    ]);

    $this->actingAs($admin);

    Livewire::test(AdministrativeRecap::class)
        ->set('studentFilter', (string) $student->id)
        ->set('lecturerFilter', (string) $lecturer->id)
        ->set('weekFilter', '6')
        ->assertSee('tabular-nums">1</td>', false);
});

test('administrative recap guidance totals exclude slots in gaps between selected periods', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create();
    $lecturer = Lecturer::factory()->create(['user_id' => User::factory()->create(['name' => 'Disjoint Period Lecturer'])->id]);
    SupervisorAssignment::factory()->create(['student_id' => $student->id, 'lecturer_id' => $lecturer->id]);
    $internship = Internship::factory()->create(['student_id' => $student->id]);

    foreach ([
        [1, '2025-02-01', '2025-02-07'],
        [2, '2025-02-21', '2025-02-27'],
    ] as [$week, $startsOn, $endsOn]) {
        ReportPeriod::factory()->create([
            'internship_id' => $internship->id,
            'week_number' => $week,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'due_at' => $endsOn.' 23:59:59',
        ]);
    }

    foreach ([
        ['2025-02-03 10:00:00', GuidanceBookingStatus::Completed],
        ['2025-02-14 10:00:00', GuidanceBookingStatus::NoShow],
        ['2025-02-24 10:00:00', GuidanceBookingStatus::Booked],
    ] as [$startsAt, $bookingStatus]) {
        $slot = GuidanceSlot::factory()->create([
            'lecturer_id' => $lecturer->id,
            'starts_at' => $startsAt,
            'ends_at' => Carbon::parse($startsAt)->addMinutes(30),
        ]);
        GuidanceBooking::factory()->create([
            'guidance_slot_id' => $slot->id,
            'student_id' => $student->id,
            'status' => $bookingStatus,
        ]);
    }

    $this->actingAs($admin);

    $component = Livewire::test(AdministrativeRecap::class)
        ->call('setReportScope', 'all')
        ->set('monthFilter', '2')
        ->set('yearFilter', '2025')
        ->set('studentFilter', (string) $student->id)
        ->set('lecturerFilter', (string) $lecturer->id);
    $guidanceRow = administrativeRecapGuidanceRowHtml($component->html(), 'Disjoint Period Lecturer');

    expect($guidanceRow)->toContain('>2</td>')
        ->and(substr_count($guidanceRow, '>1</td>'))->toBe(2)
        ->and($guidanceRow)->toContain('>0</td>')
        ->and($guidanceRow)->not->toContain('>3</td>');
});

test('lecturer widgets list pending assigned reports and count drafts only for the current lecturer', function () {
    $lecturerUser = User::factory()->create(['role' => UserRole::Lecturer]);
    $otherLecturerUser = User::factory()->create(['role' => UserRole::Lecturer]);
    $lecturer = Lecturer::factory()->create(['user_id' => $lecturerUser->id]);
    $otherLecturer = Lecturer::factory()->create(['user_id' => $otherLecturerUser->id]);
    $student = Student::factory()->create(['user_id' => User::factory()->create(['name' => 'Assigned Report Student'])->id]);
    $otherStudent = Student::factory()->create(['user_id' => User::factory()->create(['name' => 'Unassigned Report Student'])->id]);
    SupervisorAssignment::factory()->create(['student_id' => $student->id, 'lecturer_id' => $lecturer->id]);
    SupervisorAssignment::factory()->create(['student_id' => $otherStudent->id, 'lecturer_id' => $otherLecturer->id]);
    $pendingReport = makeRecapReport($student, 2);
    $otherReport = makeRecapReport($otherStudent, 3);
    Assessment::factory()->create(['weekly_report_id' => $pendingReport->id, 'lecturer_id' => $lecturer->id]);
    Assessment::factory()->create(['weekly_report_id' => $otherReport->id, 'lecturer_id' => $otherLecturer->id]);

    $this->actingAs($lecturerUser);

    Livewire::test(PendingLecturerReports::class)
        ->assertSee('Assigned Report Student')
        ->assertDontSee('Unassigned Report Student');

    Livewire::test(LecturerGuidanceStats::class)
        ->assertSee('Status laporan bimbingan')
        ->assertSee('Menunggu Diperiksa')
        ->assertSee('Penilaian draf');
});

test('admin overview reports real current period missing, late, review, and assessment counts', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create();
    $lecturer = Lecturer::factory()->create();
    SupervisorAssignment::factory()->create(['student_id' => $student->id, 'lecturer_id' => $lecturer->id]);
    $internship = Internship::factory()->create(['student_id' => $student->id]);
    $period = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'starts_on' => today()->subDay(),
        'ends_on' => today()->addDay(),
        'due_at' => now()->subMinute(),
    ]);
    $report = WeeklyReport::factory()->create([
        'report_period_id' => $period->id,
        'review_status' => ReviewStatus::PendingReview,
    ]);
    ReportVersion::factory()->create(['weekly_report_id' => $report->id, 'uploaded_by' => $student->user_id, 'is_late' => true]);
    Assessment::factory()->create(['weekly_report_id' => $report->id, 'lecturer_id' => $lecturer->id, 'status' => AssessmentStatus::Draft]);

    $this->actingAs($admin)->get('/admin')->assertOk();

    Livewire::test(AdminOverviewStats::class)
        ->assertSee('Laporan terlambat')
        ->assertSee('Laporan menunggu pemeriksaan')
        ->assertSee('Penilaian draf');
});
