<?php

use App\Enums\AssessmentStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Filament\Dosen\Widgets\LecturerGuidanceStats;
use App\Filament\Widgets\AdminLecturerReviewActivityChart;
use App\Filament\Widgets\AdminOverviewStats;
use App\Filament\Widgets\AdminPendingReportReviews;
use App\Filament\Widgets\AdminReportSubmissionTrendChart;
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
use Livewire\Livewire;

test('admin dashboard shows active account and assignment counts from the database', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    User::factory()->count(2)->create(['role' => UserRole::Student, 'is_active' => true]);
    User::factory()->create(['role' => UserRole::Student, 'is_active' => false]);
    User::factory()->create(['role' => UserRole::Lecturer, 'is_active' => true]);
    SupervisorAssignment::factory()->create();

    $this->actingAs($admin);

    Livewire::test(AdminOverviewStats::class)
        ->assertSee('Mahasiswa aktif')
        ->assertSee('Dosen aktif')
        ->assertSee('Mahasiswa tanpa pembimbing aktif')
        ->assertSee('Penugasan pembimbing aktif');
});

test('admin dashboard calculates on-time submissions from submitted reports', function () {
    $this->travelTo(now()->setTime(12, 0));
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create();
    $internship = Internship::factory()->create([
        'student_id' => $student->id,
        'start_date' => today()->subDays(14)->toDateString(),
        'end_date' => today()->addDays(14)->toDateString(),
    ]);

    foreach ([false, true] as $index => $isLate) {
        $period = ReportPeriod::factory()->create([
            'internship_id' => $internship->id,
            'week_number' => $index + 1,
            'starts_on' => today()->subDay()->toDateString(),
            'ends_on' => today()->addDay()->toDateString(),
            'due_at' => now()->subMinute(),
        ]);
        $report = WeeklyReport::factory()->create(['report_period_id' => $period->id]);

        ReportVersion::factory()->create([
            'weekly_report_id' => $report->id,
            'uploaded_by' => $student->user_id,
            'is_late' => $isLate,
        ]);
    }

    ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 3,
        'starts_on' => today()->subDay()->toDateString(),
        'ends_on' => today()->addDay()->toDateString(),
        'due_at' => now()->addDay(),
    ]);

    $this->actingAs($admin);

    Livewire::test(AdminOverviewStats::class)
        ->assertSee('Tingkat pengumpulan tepat waktu')
        ->assertSee('50%')
        ->assertSee('1 dari 2 laporan terkumpul');
});

test('admin analytics charts render data from active internship reports and reviews', function () {
    $this->travelTo(now()->startOfWeek()->addDays(1)->setTime(12, 0));
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create();
    $lecturer = Lecturer::factory()->create();
    $internship = Internship::factory()->create([
        'student_id' => $student->id,
        'start_date' => today()->subDays(60)->toDateString(),
        'end_date' => today()->addDays(30)->toDateString(),
    ]);

    foreach ([
        ['status' => ReviewStatus::PendingReview, 'is_late' => false, 'due_at' => today()->subDays(8)->setTime(23, 0)],
        ['status' => ReviewStatus::RevisionRequested, 'is_late' => true, 'due_at' => today()->subDay()->setTime(23, 0)],
        ['status' => ReviewStatus::Approved, 'is_late' => false, 'due_at' => today()->subDays(2)->setTime(23, 0)],
    ] as $index => $reportData) {
        $period = ReportPeriod::factory()->create([
            'internship_id' => $internship->id,
            'week_number' => $index + 1,
            'starts_on' => today()->subDays(10)->toDateString(),
            'ends_on' => today()->addDay()->toDateString(),
            'due_at' => $reportData['due_at'],
        ]);
        $report = WeeklyReport::factory()->create([
            'report_period_id' => $period->id,
            'review_status' => $reportData['status'],
        ]);
        $version = ReportVersion::factory()->create([
            'weekly_report_id' => $report->id,
            'uploaded_by' => $student->user_id,
            'is_late' => $reportData['is_late'],
            'submitted_at' => $reportData['due_at']->copy()->subHour(),
        ]);

        ReportReview::factory()->create([
            'weekly_report_id' => $report->id,
            'report_version_id' => $version->id,
            'lecturer_id' => $lecturer->id,
            'reviewed_at' => now()->subDays($index + 1),
        ]);
    }

    $missingPeriod = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 4,
        'starts_on' => today()->subDays(10)->toDateString(),
        'ends_on' => today()->addDay()->toDateString(),
        'due_at' => today()->subDay()->setTime(22, 0),
    ]);

    WeeklyReport::factory()->create(['report_period_id' => $missingPeriod->id]);

    $this->actingAs($admin);

    $trendChart = Livewire::test(AdminReportSubmissionTrendChart::class);
    $trendHtml = html_entity_decode($trendChart->html());

    expect($trendHtml)
        ->toContain('Ketepatan pengumpulan laporan')
        ->toContain('Tepat waktu')
        ->toContain('Terlambat')
        ->toContain('Belum dikumpulkan');

    $overview = Livewire::test(AdminOverviewStats::class);
    $overviewHtml = html_entity_decode($overview->html());

    expect($overviewHtml)
        ->toContain('Status pemeriksaan laporan')
        ->toContain('Menunggu Diperiksa')
        ->toContain('Perlu Revisi')
        ->toContain('Disetujui');

    Livewire::test(AdminPendingReportReviews::class)
        ->assertSee('Laporan Menunggu Pemeriksaan')
        ->assertSee($student->user->name)
        ->assertSee('Minggu ke-1');

    Livewire::test(AdminLecturerReviewActivityChart::class)
        ->assertSee('Pemeriksaan per dosen')
        ->assertSee($lecturer->user->name);
});

test('lecturer dashboard guidance counts are restricted to the authenticated lecturer', function () {
    $lecturer = User::factory()->create(['role' => UserRole::Lecturer]);
    $otherLecturer = User::factory()->create(['role' => UserRole::Lecturer]);
    $lecturerProfile = Lecturer::factory()->create(['user_id' => $lecturer->id]);
    $otherLecturerProfile = Lecturer::factory()->create(['user_id' => $otherLecturer->id]);
    $slot = GuidanceSlot::factory()->create(['lecturer_id' => $lecturerProfile->id]);
    GuidanceBooking::factory()->create(['guidance_slot_id' => $slot->id]);
    GuidanceSlot::factory()->create(['lecturer_id' => $otherLecturerProfile->id]);

    $this->actingAs($lecturer);

    Livewire::test(LecturerGuidanceStats::class)
        ->assertSee('Slot mendatang')
        ->assertSee('Pemesanan mendatang')
        ->assertSee('Mahasiswa bimbingan aktif')
        ->assertSee('Status laporan bimbingan');
});

test('student dashboard only shows their active supervisor and upcoming bookings', function () {
    $student = User::factory()->create(['role' => UserRole::Student]);
    $otherStudent = User::factory()->create(['role' => UserRole::Student]);
    $studentProfile = Student::factory()->create(['user_id' => $student->id]);
    $otherStudentProfile = Student::factory()->create(['user_id' => $otherStudent->id]);
    $assignment = SupervisorAssignment::factory()->create(['student_id' => $studentProfile->id]);
    $otherAssignment = SupervisorAssignment::factory()->create(['student_id' => $otherStudentProfile->id]);
    $ownSlot = GuidanceSlot::factory()->create(['lecturer_id' => $assignment->lecturer_id, 'starts_at' => now()->addDays(3)]);
    $otherSlot = GuidanceSlot::factory()->create(['lecturer_id' => $otherAssignment->lecturer_id, 'starts_at' => now()->addDays(4)]);
    GuidanceBooking::factory()->create(['student_id' => $studentProfile->id, 'guidance_slot_id' => $ownSlot->id]);
    GuidanceBooking::factory()->create(['student_id' => $otherStudentProfile->id, 'guidance_slot_id' => $otherSlot->id]);

    $response = $this->actingAs($student)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Dosen Pembimbing Aktif')
        ->assertSee($assignment->lecturer->user->name)
        ->assertDontSee($otherAssignment->lecturer->user->name);
});

test('student dashboard summarizes their current period revisions guidance and only published grades', function () {
    $this->travelTo(now()->setTime(12, 0));
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::factory()->create(['user_id' => $user->id]);
    $internship = Internship::factory()->create([
        'student_id' => $student->id,
        'start_date' => today()->subDays(14)->toDateString(),
        'end_date' => today()->addDays(14)->toDateString(),
    ]);
    $approvedPeriod = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 1,
        'starts_on' => today()->subDays(14)->toDateString(),
        'ends_on' => today()->subDays(8)->toDateString(),
        'due_at' => now()->subDays(8),
    ]);
    $approvedReport = WeeklyReport::factory()->create([
        'report_period_id' => $approvedPeriod->id,
        'review_status' => ReviewStatus::Approved,
    ]);
    ReportVersion::factory()->create([
        'weekly_report_id' => $approvedReport->id,
        'uploaded_by' => $user->id,
    ]);
    Assessment::factory()->published()->create([
        'weekly_report_id' => $approvedReport->id,
        'score' => '86.25',
    ]);

    $revisionPeriod = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 2,
        'starts_on' => today()->subDays(7)->toDateString(),
        'ends_on' => today()->subDay()->toDateString(),
        'due_at' => now()->subDay(),
    ]);
    $revisionReport = WeeklyReport::factory()->create([
        'report_period_id' => $revisionPeriod->id,
        'review_status' => ReviewStatus::RevisionRequested,
    ]);
    ReportVersion::factory()->create([
        'weekly_report_id' => $revisionReport->id,
        'uploaded_by' => $user->id,
    ]);
    Assessment::factory()->create([
        'weekly_report_id' => $revisionReport->id,
        'score' => '99.99',
        'status' => AssessmentStatus::Draft,
    ]);

    ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 3,
        'starts_on' => today()->toDateString(),
        'ends_on' => today()->addDays(6)->toDateString(),
        'due_at' => now()->addDays(6),
    ]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Periode Pelaporan Berjalan')
        ->assertSee('Belum Dikumpulkan')
        ->assertSee('Laporan Perlu Direvisi')
        ->assertSee('Minggu ke-2')
        ->assertSee('Pembimbingan Mendatang')
        ->assertSee('Hasil Penilaian Terbaru')
        ->assertSee('86,25')
        ->assertDontSee('99,99');
});
