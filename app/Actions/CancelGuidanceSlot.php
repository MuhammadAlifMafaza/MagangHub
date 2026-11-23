<?php

namespace App\Actions;

use App\Enums\GuidanceBookingStatus;
use App\Enums\GuidanceSlotStatus;
use App\Models\GuidanceSlot;
use App\Models\Lecturer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelGuidanceSlot
{
    public function handle(GuidanceSlot $slot, Lecturer $lecturer, string $reason): GuidanceSlot
    {
        $cancelledBookings = collect();
        $cancelledSlot = DB::transaction(function () use ($slot, $lecturer, $reason, &$cancelledBookings): GuidanceSlot {
            $lockedSlot = GuidanceSlot::query()->whereKey($slot->id)->lockForUpdate()->firstOrFail();

            if ($lockedSlot->lecturer_id !== $lecturer->id || $lockedSlot->status !== GuidanceSlotStatus::Open) {
                throw ValidationException::withMessages(['slot' => 'Slot tidak dapat dibatalkan.']);
            }

            $cancelledBookings = $lockedSlot->bookings()->where('status', GuidanceBookingStatus::Booked)->with('student.user')->get();
            $lockedSlot->update(['status' => GuidanceSlotStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => $reason]);
            $lockedSlot->bookings()->where('status', GuidanceBookingStatus::Booked)->update([
                'status' => GuidanceBookingStatus::Cancelled->value,
                'cancelled_at' => now(),
                'cancelled_by' => $lecturer->user_id,
                'cancel_reason' => $reason,
            ]);

            return $lockedSlot->refresh();
        });

        foreach ($cancelledBookings as $booking) {
            app(SendWorkflowNotification::class)->handle(
                $booking->student->user,
                'guidance_cancelled_by_lecturer',
                'Jadwal bimbingan dibatalkan',
                'Jadwal bimbingan pada '.$cancelledSlot->starts_at->format('d M Y H:i').' dibatalkan. Alasan: '.$reason,
                route('guidance.student', absolute: false),
                'Lihat jadwal',
            );
        }

        return $cancelledSlot;
    }
}
