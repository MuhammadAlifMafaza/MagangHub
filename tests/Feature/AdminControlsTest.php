<?php

use App\Actions\EndSupervisorAssignment;
use App\Actions\UpdateAccount;
use App\Actions\UpdateInternship;
use App\Enums\GuidanceBookingStatus;
use App\Enums\InternshipStatus;
use App\Enums\SupervisorAssignmentStatus;
use App\Enums\UserRole;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSlot;
use App\Models\Internship;
use App\Models\Lecturer;
use App\Models\ReportPeriod;
use App\Models\Student;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Validation\ValidationException;

test('admin updates a student account and profile without changing the account role', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create();

    $updatedUser = app(UpdateAccount::class)->handle($admin, $student->user, [
        'name' => 'Nama Mahasiswa Baru',
        'email' => $student->user->email,
        'role' => UserRole::Admin->value,
        'nim' => '23.230.1001',
        'study_program' => 'Teknik Informatika',
        'cohort_year' => 2024,
        'phone' => '081234567890',
    ]);

    expect($updatedUser->name)->toBe('Nama Mahasiswa Baru')
        ->and($updatedUser->role)->toBe(UserRole::Student)
        ->and($updatedUser->student->nim)->toBe('23.230.1001')
        ->and($updatedUser->student->study_program)->toBe('Teknik Informatika');
});

test('admin account updates reject duplicate student identifiers', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create();
    $otherStudent = Student::factory()->create();

    expect(fn () => app(UpdateAccount::class)->handle($admin, $student->user, [
        'name' => $student->user->name,
        'email' => $student->user->email,
        'nim' => $otherStudent->nim,
        'study_program' => $student->study_program,
        'cohort_year' => $student->cohort_year,
        'phone' => $student->phone,
    ]))->toThrow(ValidationException::class);

    expect($student->fresh()->nim)->not->toBe($otherStudent->nim);
});

test('admin account updates reject a lecturer NIDN already used as a student NIM', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $lecturer = Lecturer::factory()->create(['nidn' => '1234567890']);
    $student = Student::factory()->create(['nim' => '0987654321']);

    expect(fn () => app(UpdateAccount::class)->handle($admin, $lecturer->user, [
        'name' => $lecturer->user->name,
        'email' => $lecturer->user->email,
        'nidn' => $student->nim,
    ]))->toThrow(ValidationException::class);

    expect($lecturer->fresh()->nidn)->toBe('1234567890');
});

test('admin internship updates retain reported periods and synchronize unreported periods', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $internship = Internship::factory()->create([
        'start_date' => '2026-02-01',
        'end_date' => '2026-02-14',
        'status' => InternshipStatus::Active,
    ]);
    $reportedPeriod = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 1,
        'starts_on' => '2026-02-01',
        'ends_on' => '2026-02-07',
        'due_at' => '2026-02-07 23:59:59',
    ]);
    WeeklyReport::factory()->create(['report_period_id' => $reportedPeriod->id]);
    $unreportedPeriod = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 2,
        'starts_on' => '2026-02-08',
        'ends_on' => '2026-02-14',
        'due_at' => '2026-02-14 23:59:59',
    ]);

    app(UpdateInternship::class)->handle($admin, $internship, [
        'company_name' => 'Instansi Diperbarui',
        'company_supervisor_name' => null,
        'start_date' => '2026-02-01',
        'end_date' => '2026-02-21',
        'status' => InternshipStatus::Active->value,
    ]);

    expect($reportedPeriod->fresh()->ends_on->toDateString())->toBe('2026-02-07')
        ->and($unreportedPeriod->fresh()->ends_on->toDateString())->toBe('2026-02-14')
        ->and($internship->reportPeriods()->count())->toBe(3)
        ->and($internship->fresh()->company_name)->toBe('Instansi Diperbarui');
});

test('ending a supervisor assignment cancels future bookings with the given reason', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create();
    $lecturer = Lecturer::factory()->create();
    $assignment = SupervisorAssignment::factory()->create([
        'student_id' => $student->id,
        'lecturer_id' => $lecturer->id,
        'status' => SupervisorAssignmentStatus::Active,
    ]);
    $slot = GuidanceSlot::factory()->create([
        'lecturer_id' => $lecturer->id,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addMinutes(30),
    ]);
    $booking = GuidanceBooking::factory()->create([
        'guidance_slot_id' => $slot->id,
        'student_id' => $student->id,
        'status' => GuidanceBookingStatus::Booked,
    ]);

    app(EndSupervisorAssignment::class)->handle($assignment, $admin, 'Pergantian pembimbing');

    expect($assignment->fresh()->status)->toBe(SupervisorAssignmentStatus::Ended)
        ->and($booking->fresh()->status)->toBe(GuidanceBookingStatus::Cancelled)
        ->and($booking->fresh()->cancel_reason)->toBe('Pergantian pembimbing');
});

test('admin booking oversight is restricted to the admin panel', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $lecturer = User::factory()->create(['role' => UserRole::Lecturer]);

    $this->actingAs($admin)->get('/admin/guidance-bookings')->assertOk();
    $this->actingAs($lecturer)->get('/admin/guidance-bookings')->assertForbidden();
});
