<?php

namespace App\Actions;

use App\Enums\GuidanceBookingStatus;
use App\Enums\GuidanceSlotStatus;
use App\Enums\SupervisorAssignmentStatus;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSlot;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookGuidanceSlot
{
    public function handle(Student $student, GuidanceSlot $slot): GuidanceBooking
    {
        $booking = DB::transaction(function () use ($student, $slot): GuidanceBooking {
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->id);
            $lockedSlot = GuidanceSlot::query()->lockForUpdate()->findOrFail($slot->id);
            $cutoff = config('magang.guidance.booking_cutoff_hours');
            $hasActiveSupervisor = $lockedStudent->activeSupervisorAssignment()
                ->where('lecturer_id', $lockedSlot->lecturer_id)
                ->where('status', SupervisorAssignmentStatus::Active)
                ->exists();

            if (! $hasActiveSupervisor || $lockedSlot->status !== GuidanceSlotStatus::Open || $lockedSlot->starts_at->lessThanOrEqualTo(now()->addHours($cutoff))) {
                throw ValidationException::withMessages(['slot' => 'Slot tidak tersedia atau bukan milik pembimbing aktif Anda.']);
            }

            if ($lockedStudent->guidanceBookings()->where('status', GuidanceBookingStatus::Booked)->count() >= config('magang.guidance.maximum_active_bookings')) {
                throw ValidationException::withMessages(['slot' => 'Anda telah mencapai batas pemesanan aktif.']);
            }

            if ($lockedSlot->bookings()->where('status', '!=', GuidanceBookingStatus::Cancelled)->count() >= $lockedSlot->capacity) {
                throw ValidationException::withMessages(['slot' => 'Slot ini baru saja dipesan dan tidak lagi tersedia. Silakan pilih slot lain.']);
            }

            return $lockedSlot->bookings()->create(['student_id' => $lockedStudent->id, 'status' => GuidanceBookingStatus::Booked]);
        });

        $booking->load('student.user', 'slot.lecturer.user');
        $lecturerUser = $booking->slot->lecturer->user;
        $studentUser = $booking->student->user;

        app(SendWorkflowNotification::class)->handle(
            $lecturerUser,
            'guidance_booked',
            'Jadwal bimbingan baru',
            $studentUser->name.' memesan jadwal bimbingan pada '.$booking->slot->starts_at->format('d M Y H:i').'.',
            '/dosen',
            'Buka panel dosen',
        );
        app(SendWorkflowNotification::class)->handle(
            $studentUser,
            'guidance_booking_confirmed',
            'Jadwal bimbingan terkonfirmasi',
            'Jadwal bimbingan Anda pada '.$booking->slot->starts_at->format('d M Y H:i').' telah dikonfirmasi.',
            route('guidance.student', absolute: false),
            'Lihat jadwal',
        );

        return $booking;
    }
}
