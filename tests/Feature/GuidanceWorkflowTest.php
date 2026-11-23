<?php

use App\Actions\BookGuidanceSlot;
use App\Actions\CancelGuidanceBooking;
use App\Actions\CancelGuidanceSlot;
use App\Actions\CreateGuidanceSlot;
use App\Actions\RecordGuidanceBookingOutcome;
use App\Actions\UpdateGuidanceSlot;
use App\Enums\GuidanceBookingStatus;
use App\Enums\GuidanceSlotStatus;
use App\Enums\SupervisorAssignmentStatus;
use App\Enums\UserRole;
use App\Filament\Dosen\Resources\GuidanceBookings\Pages\ManageGuidanceBookings;
use App\Filament\Dosen\Resources\GuidanceSlots\Pages\ManageGuidanceSlots;
use App\Filament\Dosen\Widgets\LecturerGuidanceStats;
use App\Livewire\StudentGuidance;
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
use Database\Seeders\GuidanceDemoSeeder;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function assignGuidanceStudent(Student $student, Lecturer $lecturer): void
{
    SupervisorAssignment::factory()->create([
        'student_id' => $student->id,
        'lecturer_id' => $lecturer->id,
        'status' => SupervisorAssignmentStatus::Active,
        'assigned_by' => User::factory()->create(['role' => UserRole::Admin])->id,
    ]);
}

test('guidance demo seeder is repeatable and creates an assigned student with a booked future slot', function () {
    Storage::fake('local');

    app(GuidanceDemoSeeder::class)->run();
    app(GuidanceDemoSeeder::class)->run();

    expect(User::query()->where('email', 'mahasiswa.demo@maganghub.test')->count())->toBe(1)
        ->and(Student::query()->count())->toBe(1)
        ->and(Internship::query()->count())->toBe(1)
        ->and(ReportPeriod::query()->count())->toBeGreaterThan(1)
        ->and(WeeklyReport::query()->count())->toBe(2)
        ->and(ReportVersion::query()->count())->toBe(2)
        ->and(ReportReview::query()->count())->toBe(1)
        ->and(Assessment::query()->count())->toBe(1)
        ->and(GuidanceSlot::query()->count())->toBe(1)
        ->and(GuidanceBooking::query()->count())->toBe(1)
        ->and(GuidanceBooking::query()->first()->slot->starts_at->isFuture())->toBeTrue();
});

test('lecturer can create future slots but overlapping slots are rejected', function () {
    $lecturer = Lecturer::factory()->create();
    $startsAt = now()->addDays(3)->setTime(10, 0);
    $endsAt = $startsAt->copy()->addMinutes(30);
    $action = app(CreateGuidanceSlot::class);

    $slot = $action->handle($lecturer, ['starts_at' => $startsAt->toDateTimeString(), 'ends_at' => $endsAt->toDateTimeString()]);

    expect($slot->capacity)->toBe(config('magang.guidance.default_capacity'));
    expect(fn () => $action->handle($lecturer, [
        'starts_at' => $startsAt->copy()->addMinutes(15)->toDateTimeString(),
        'ends_at' => $endsAt->copy()->addMinutes(15)->toDateTimeString(),
    ]))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('guidance_slots', 1);
});

test('only the active advisee can book a slot and cancelling releases capacity', function () {
    $lecturer = Lecturer::factory()->create();
    $student = Student::factory()->create();
    $otherStudent = Student::factory()->create();
    assignGuidanceStudent($student, $lecturer);
    $slot = GuidanceSlot::factory()->create(['lecturer_id' => $lecturer->id]);
    $book = app(BookGuidanceSlot::class);

    expect(fn () => $book->handle($otherStudent, $slot))->toThrow(ValidationException::class);

    $booking = $book->handle($student, $slot);
    expect(fn () => $book->handle($student, $slot))->toThrow(ValidationException::class);

    app(CancelGuidanceBooking::class)->handle($booking, $student, 'Ada keperluan');
    expect($booking->fresh()->status)->toBe(GuidanceBookingStatus::Cancelled);

    $replacementStudent = Student::factory()->create();
    assignGuidanceStudent($replacementStudent, $lecturer);
    expect(app(BookGuidanceSlot::class)->handle($replacementStudent, $slot)->status)->toBe(GuidanceBookingStatus::Booked);
});

test('cancelling a slot cancels its active bookings and records an outcome after start', function () {
    $lecturer = Lecturer::factory()->create();
    $student = Student::factory()->create();
    $slot = GuidanceSlot::factory()->create([
        'lecturer_id' => $lecturer->id,
        'starts_at' => now()->subMinutes(5),
        'ends_at' => now()->addMinutes(25),
    ]);
    $booking = GuidanceBooking::factory()->create(['guidance_slot_id' => $slot->id, 'student_id' => $student->id]);

    $result = app(RecordGuidanceBookingOutcome::class)->handle($booking, $lecturer, GuidanceBookingStatus::Completed, 'Diskusi progres');
    expect($result->status)->toBe(GuidanceBookingStatus::Completed)->and($result->lecturer_notes)->toBe('Diskusi progres');

    $cancelledSlot = GuidanceSlot::factory()->create(['lecturer_id' => $lecturer->id]);
    $cancelledBooking = GuidanceBooking::factory()->create(['guidance_slot_id' => $cancelledSlot->id]);
    app(CancelGuidanceSlot::class)->handle($cancelledSlot, $lecturer, 'Dosen berhalangan');
    expect($cancelledBooking->fresh()->status)->toBe(GuidanceBookingStatus::Cancelled)
        ->and($cancelledBooking->fresh()->cancel_reason)->toBe('Dosen berhalangan');
});

test('student guidance only shows active supervisor slots and permits own booking cancellation', function () {
    $lecturer = Lecturer::factory()->create();
    $otherLecturer = Lecturer::factory()->create();
    $student = Student::factory()->create();
    assignGuidanceStudent($student, $lecturer);
    $visibleSlot = GuidanceSlot::factory()->create(['lecturer_id' => $lecturer->id]);
    $hiddenSlot = GuidanceSlot::factory()->create(['lecturer_id' => $otherLecturer->id]);
    $hiddenSlot->update(['location' => 'Lokasi privat dosen lain']);

    expect($student->activeSupervisorAssignment()->value('lecturer_id'))->toBe($lecturer->id)
        ->and($visibleSlot->starts_at->isFuture())->toBeTrue()
        ->and(GuidanceSlot::query()->where('lecturer_id', $lecturer->id)->where('status', GuidanceSlotStatus::Open)->count())->toBe(1)
        ->and(GuidanceSlot::query()->where('lecturer_id', $lecturer->id)->where('status', GuidanceSlotStatus::Open)->where('starts_at', '>', now())->count())->toBe(1)
        ->and($student->user->student->id)->toBe($student->id);

    $guidance = Livewire::actingAs($student->user)->test(StudentGuidance::class)
        ->assertSee('Pesan slot')
        ->assertDontSee('Lokasi privat dosen lain')
        ->call('confirmBooking', $visibleSlot->id)
        ->assertSee('Konfirmasi pemesanan')
        ->assertSet('bookingToConfirm', $visibleSlot->id)
        ->assertSet('showBookingConfirmation', true);

    $this->assertDatabaseCount('guidance_bookings', 0);

    $guidance->call('book')
        ->assertSee('berhasil dipesan')
        ->assertSet('showBookingConfirmation', false);

    $booking = $student->guidanceBookings()->firstOrFail();
    Livewire::actingAs($student->user)->test(StudentGuidance::class)
        ->call('cancel', $booking->id)
        ->assertSee('berhasil dibatalkan');

    expect($booking->fresh()->status)->toBe(GuidanceBookingStatus::Cancelled);
});

test('lecturer may update location after booking but cannot change booked slot times or reduce capacity below occupancy', function () {
    $lecturer = Lecturer::factory()->create();
    $student = Student::factory()->create();
    assignGuidanceStudent($student, $lecturer);
    $slot = GuidanceSlot::factory()->create(['lecturer_id' => $lecturer->id]);
    GuidanceBooking::factory()->create(['guidance_slot_id' => $slot->id, 'student_id' => $student->id]);
    $action = app(UpdateGuidanceSlot::class);
    $unchangedTimes = [
        'starts_at' => $slot->starts_at->toDateTimeString(),
        'ends_at' => $slot->ends_at->toDateTimeString(),
        'capacity' => 1,
        'location' => 'Ruang baru',
    ];

    $updated = $action->handle($slot, $lecturer, $unchangedTimes);
    expect($updated->location)->toBe('Ruang baru');

    expect(fn () => $action->handle($slot->fresh(), $lecturer, [...$unchangedTimes, 'capacity' => 2, 'starts_at' => $slot->starts_at->copy()->addHour()->toDateTimeString()]))
        ->toThrow(ValidationException::class);
});

test('student cannot invoke booking or cancellation against another students records', function () {
    $lecturer = Lecturer::factory()->create();
    $student = Student::factory()->create();
    $otherStudent = Student::factory()->create();
    assignGuidanceStudent($student, $lecturer);
    $slot = GuidanceSlot::factory()->create(['lecturer_id' => $lecturer->id]);
    $booking = app(BookGuidanceSlot::class)->handle($student, $slot);

    Livewire::actingAs($otherStudent->user)->test(StudentGuidance::class)
        ->call('cancel', $booking->id)
        ->assertNotFound();
});

test('student guidance route lists only the active supervisors slots', function () {
    $lecturer = Lecturer::factory()->create();
    $otherLecturer = Lecturer::factory()->create();
    $student = Student::factory()->create();
    assignGuidanceStudent($student, $lecturer);
    GuidanceSlot::factory()->create(['lecturer_id' => $lecturer->id, 'location' => 'Klinik laporan']);
    GuidanceSlot::factory()->create(['lecturer_id' => $otherLecturer->id, 'location' => 'Slot rahasia dosen lain']);

    $this->actingAs($student->user)
        ->get('/bimbingan')
        ->assertOk()
        ->assertSee('Klinik laporan')
        ->assertDontSee('Slot rahasia dosen lain');
});

test('lecturer Filament guidance resources are available and scoped to their slots and bookings', function () {
    $lecturer = Lecturer::factory()->create();
    $otherLecturer = Lecturer::factory()->create();
    $student = Student::factory()->create();
    $ownSlot = GuidanceSlot::factory()->create(['lecturer_id' => $lecturer->id, 'location' => 'Jadwal milik saya']);
    GuidanceSlot::factory()->create(['lecturer_id' => $otherLecturer->id, 'location' => 'Jadwal milik dosen lain']);
    GuidanceBooking::factory()->create(['guidance_slot_id' => $ownSlot->id, 'student_id' => $student->id]);

    $this->actingAs($lecturer->user)
        ->get('/dosen/guidance-slots')
        ->assertOk()
        ->assertSee('Jadwal milik saya')
        ->assertDontSee('Jadwal milik dosen lain');

    $this->actingAs($lecturer->user)->get('/dosen/guidance-bookings')
        ->assertOk()
        ->assertSee('Pemesanan Bimbingan Mahasiswa')
        ->assertSee($student->user->name);

    Filament::setCurrentPanel('dosen');

    Livewire::actingAs($lecturer->user)
        ->test(LecturerGuidanceStats::class)
        ->assertSee('Ringkasan aktivitas bimbingan')
        ->assertSee('Status laporan bimbingan')
        ->assertSee('Mahasiswa bimbingan aktif');

    $this->actingAs($student->user)->get('/dosen/guidance-slots')->assertForbidden();
});

test('lecturer can create and update slots from the Filament slot actions', function () {
    $lecturer = Lecturer::factory()->create();
    $startsAt = now()->addDays(5)->setTime(10, 0);
    $endsAt = $startsAt->copy()->addMinutes(30);
    $component = Livewire::actingAs($lecturer->user)->test(ManageGuidanceSlots::class);

    $component->mountTableAction('createSlot')
        ->fillForm([
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
            'capacity' => 2,
            'location' => 'Ruang A',
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors();

    $slot = GuidanceSlot::query()->where('lecturer_id', $lecturer->id)->firstOrFail();

    expect($slot->location)->toBe('Ruang A');

    $updatedStartsAt = $startsAt->copy()->addHour();
    $component->mountTableAction('editSlot', $slot)
        ->fillForm([
            'starts_at' => $updatedStartsAt->toDateTimeString(),
            'ends_at' => $updatedStartsAt->copy()->addMinutes(30)->toDateTimeString(),
            'capacity' => 3,
            'location' => 'Ruang B',
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors();

    expect($slot->fresh()->location)->toBe('Ruang B')
        ->and($slot->fresh()->capacity)->toBe(3);

    $component->assertTableActionExists('viewSlot', record: $slot)
        ->assertTableActionHasIcon('viewSlot', Heroicon::OutlinedEye, $slot);
});

test('slot form explains and rejects durations longer than the configured maximum', function () {
    $lecturer = Lecturer::factory()->create();
    $startsAt = now()->addDays(5)->setTime(10, 0);

    Livewire::actingAs($lecturer->user)
        ->test(ManageGuidanceSlots::class)
        ->mountTableAction('createSlot')
        ->fillForm([
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $startsAt->copy()->addHours(3)->toDateTimeString(),
            'capacity' => 2,
            'location' => 'Ruang A',
        ])
        ->callMountedAction()
        ->assertHasFormErrors(['ends_at']);

    expect(GuidanceSlot::query()->where('lecturer_id', $lecturer->id)->exists())->toBeFalse();
});

test('lecturer booking actions have clear labels and icons', function () {
    $lecturer = Lecturer::factory()->create();
    $student = Student::factory()->create();
    $slot = GuidanceSlot::factory()->create([
        'lecturer_id' => $lecturer->id,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(30),
    ]);
    $booking = GuidanceBooking::factory()->create([
        'guidance_slot_id' => $slot->id,
        'student_id' => $student->id,
    ]);
    Filament::setCurrentPanel('dosen');

    Livewire::actingAs($lecturer->user)
        ->test(ManageGuidanceBookings::class)
        ->assertTableActionHasIcon('completed', Heroicon::CheckCircle, $booking)
        ->assertTableActionHasIcon('noShow', Heroicon::OutlinedUserMinus, $booking)
        ->assertTableActionHasIcon('cancelBooking', Heroicon::OutlinedXCircle, $booking);
});
