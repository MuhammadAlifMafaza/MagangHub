<?php

use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Models\Internship;
use App\Models\Lecturer;
use App\Models\ReportPeriod;
use App\Models\ReportVersion;
use App\Models\Student;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Support\Facades\Storage;

test('lecturer report queue lists only pending reports for active supervisor assignments', function () {
    $lecturer = Lecturer::factory()->create();
    $user = User::findOrFail($lecturer->user_id);
    $assignedStudent = Student::factory()->create();
    $inactiveStudent = Student::factory()->create();
    SupervisorAssignment::factory()->create(['lecturer_id' => $lecturer->id, 'student_id' => $assignedStudent->id, 'status' => 'active']);
    SupervisorAssignment::factory()->create(['lecturer_id' => $lecturer->id, 'student_id' => $inactiveStudent->id, 'status' => 'ended']);

    $makePendingReport = function (Student $student): WeeklyReport {
        $period = ReportPeriod::factory()->create(['internship_id' => Internship::factory()->create(['student_id' => $student->id])]);

        return WeeklyReport::factory()->create(['report_period_id' => $period->id, 'review_status' => ReviewStatus::PendingReview]);
    };

    $visibleReport = $makePendingReport($assignedStudent);
    $hiddenReport = $makePendingReport($inactiveStudent);

    $this->actingAs($user)->get('/dosen/report-reviews')
        ->assertOk()
        ->assertSee($assignedStudent->user->name)
        ->assertDontSee($inactiveStudent->user->name)
        ->assertSee('Pemeriksaan');

    expect($visibleReport->exists)->toBeTrue()->and($hiddenReport->exists)->toBeTrue();
});

test('lecturer report queue keeps access to historical periods covered by an ended assignment', function () {
    $lecturer = Lecturer::factory()->create();
    $student = Student::factory()->create();
    SupervisorAssignment::factory()->create([
        'lecturer_id' => $lecturer->id,
        'student_id' => $student->id,
        'status' => 'ended',
        'assigned_at' => '2026-01-01 00:00:00',
        'ended_at' => '2026-01-15 23:59:59',
    ]);
    $period = ReportPeriod::factory()->create([
        'internship_id' => Internship::factory()->create(['student_id' => $student->id]),
        'starts_on' => '2026-01-10',
        'due_at' => '2026-01-16 23:59:59',
    ]);
    $report = WeeklyReport::factory()->create([
        'report_period_id' => $period->id,
        'review_status' => ReviewStatus::PendingReview,
    ]);
    ReportVersion::factory()->create([
        'weekly_report_id' => $report->id,
        'uploaded_by' => $student->user_id,
    ]);

    $this->actingAs($lecturer->user)
        ->get('/dosen/report-reviews')
        ->assertOk()
        ->assertSee($student->user->name)
        ->assertSee('Pemeriksaan');
});

test('administrator cannot access lecturer report review queue', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get('/dosen/report-reviews')->assertForbidden();
});

test('lecturer report details explain when an uploaded document is missing', function () {
    Storage::fake('local');
    $lecturer = Lecturer::factory()->create();
    $student = Student::factory()->create();
    SupervisorAssignment::factory()->create([
        'lecturer_id' => $lecturer->id,
        'student_id' => $student->id,
        'status' => 'active',
    ]);
    $period = ReportPeriod::factory()->create([
        'internship_id' => Internship::factory()->create(['student_id' => $student->id]),
    ]);
    $report = WeeklyReport::factory()->create([
        'report_period_id' => $period->id,
        'review_status' => ReviewStatus::PendingReview,
    ]);
    ReportVersion::factory()->create([
        'weekly_report_id' => $report->id,
        'uploaded_by' => $student->user_id,
    ]);
    $this->actingAs($lecturer->user)
        ->get('/dosen/report-reviews')
        ->assertOk()
        ->assertSee('Berkas fisik tidak ditemukan. Hubungi administrator untuk pemulihan.');
});
