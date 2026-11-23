<?php

use App\Actions\AssignSupervisor;
use App\Actions\CreateInternship;
use App\Actions\EndSupervisorAssignment;
use App\Enums\InternshipStatus;
use App\Enums\SupervisorAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Internship;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('creating an internship generates non-overlapping weekly periods including a short final period', function () {
    $student = Student::factory()->create();

    $internship = app(CreateInternship::class)->handle($student, [
        'company_name' => 'Perusahaan Contoh',
        'company_supervisor_name' => 'Pembimbing Lapangan',
        'start_date' => '2026-08-03',
        'end_date' => '2026-09-25',
    ]);

    $periods = $internship->reportPeriods()->orderBy('week_number')->get();

    expect($internship->status)->toBe(InternshipStatus::Active)
        ->and($periods)->toHaveCount(8)
        ->and($periods->first()->starts_on->toDateString())->toBe('2026-08-03')
        ->and($periods->first()->ends_on->toDateString())->toBe('2026-08-09')
        ->and($periods->last()->starts_on->toDateString())->toBe('2026-09-21')
        ->and($periods->last()->ends_on->toDateString())->toBe('2026-09-25')
        ->and($periods->last()->due_at->format('H:i:s'))->toBe('23:59:59')
        ->and($periods->pluck('week_number')->all())->toBe(range(1, 8));
});

test('creating an internship rejects an end date before its start date', function () {
    $student = Student::factory()->create();

    expect(fn () => app(CreateInternship::class)->handle($student, [
        'company_name' => 'Perusahaan Contoh',
        'start_date' => '2026-08-10',
        'end_date' => '2026-08-09',
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('internships', 0);
    $this->assertDatabaseCount('report_periods', 0);
});

test('a student cannot receive a second active internship when the rule is enabled', function () {
    config()->set('magang.internships.one_active_per_student', true);
    $student = Student::factory()->create();
    Internship::factory()->create(['student_id' => $student->id]);

    expect(fn () => app(CreateInternship::class)->handle($student, [
        'company_name' => 'Perusahaan Kedua',
        'start_date' => '2026-08-03',
        'end_date' => '2026-08-09',
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('internships', 1);
});

test('admin can assign one active lecturer to a student and duplicate active assignments are rejected', function () {
    $student = Student::factory()->create();
    $lecturer = Lecturer::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $assignment = app(AssignSupervisor::class)->handle($student, $lecturer, $admin);

    expect($assignment->fresh()->status)->toBe(SupervisorAssignmentStatus::Active)
        ->and($assignment->assigned_by)->toBe($admin->id);

    expect(fn () => app(AssignSupervisor::class)->handle($student, $lecturer, $admin))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('supervisor_assignments', 1);
});

test('inactive lecturer accounts cannot receive supervisor assignments', function () {
    $student = Student::factory()->create();
    $lecturer = Lecturer::factory()->create();
    $lecturer->user->forceFill(['is_active' => false])->save();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    expect(fn () => app(AssignSupervisor::class)->handle($student, $lecturer, $admin))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('supervisor_assignments', 0);
});

test('ending an active supervisor assignment retains history and allows a replacement assignment', function () {
    $student = Student::factory()->create();
    $oldLecturer = Lecturer::factory()->create();
    $newLecturer = Lecturer::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $assignment = app(AssignSupervisor::class)->handle($student, $oldLecturer, $admin);

    app(EndSupervisorAssignment::class)->handle($assignment, $admin, 'Perubahan pembimbing');
    $replacement = app(AssignSupervisor::class)->handle($student, $newLecturer, $admin);

    expect($assignment->fresh()->status)->toBe(SupervisorAssignmentStatus::Ended)
        ->and($assignment->fresh()->end_reason)->toBe('Perubahan pembimbing')
        ->and($assignment->fresh()->ended_at)->not->toBeNull()
        ->and($replacement->status)->toBe(SupervisorAssignmentStatus::Active);

    $this->assertDatabaseCount('supervisor_assignments', 2);
});

test('admin Filament resources for internships and supervisor assignments are available only to admins', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $lecturer = User::factory()->create(['role' => UserRole::Lecturer]);

    $this->actingAs($admin)->get('/admin/internships')->assertOk();
    $this->actingAs($admin)->get('/admin/supervisor-assignments')->assertOk();
    $this->actingAs($admin)->get('/admin/report-periods')->assertOk();
    $this->actingAs($lecturer)->get('/admin/internships')->assertForbidden();
    $this->actingAs($lecturer)->get('/admin/supervisor-assignments')->assertForbidden();
    $this->actingAs($lecturer)->get('/admin/report-periods')->assertForbidden();
});
