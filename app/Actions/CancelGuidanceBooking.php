<?php

namespace App\Actions;

use App\Enums\GuidanceBookingStatus;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSlot;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelGuidanceBooking
{
    public function handle(GuidanceBooking $booking, Student $student, ?string $reason = null): GuidanceBooking
    {
        return DB::transaction(function () use ($booking, $student, $reason): GuidanceBooking {
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->id);
            $slot = GuidanceSlot::query()->whereKey($booking->guidance_slot_id)->lockForUpdate()->firstOrFail();
            $lockedBooking = GuidanceBooking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $cutoff = config('magang.guidance.cancellation_cutoff_hours');

            if ($lockedBooking->student_id !== $lockedStudent->id || $lockedBooking->status !== GuidanceBookingStatus::Booked || $slot->starts_at->lessThanOrEqualTo(now()->addHours($cutoff))) {
                throw ValidationException::withMessages(['booking' => 'Pemesanan tidak dapat dibatalkan pada saat ini.']);
            }

            $lockedBooking->update([
                'status' => GuidanceBookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $lockedStudent->user_id,
                'cancel_reason' => $reason,
            ]);

            return $lockedBooking->refresh();
        });
    }
}
