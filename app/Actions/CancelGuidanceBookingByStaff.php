<?php

namespace App\Actions;

use App\Enums\GuidanceBookingStatus;
use App\Enums\UserRole;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSlot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelGuidanceBookingByStaff
{
    public function handle(GuidanceBooking $booking, User $actor, string $reason): GuidanceBooking
    {
        return DB::transaction(function () use ($booking, $actor, $reason): GuidanceBooking {
            $slot = GuidanceSlot::query()->whereKey($booking->guidance_slot_id)->lockForUpdate()->firstOrFail();
            $lockedBooking = GuidanceBooking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $canCancel = $actor->is_active && ($actor->role === UserRole::Admin || ($actor->role === UserRole::Lecturer && $slot->lecturer->user_id === $actor->id));

            if (! $canCancel || $lockedBooking->status !== GuidanceBookingStatus::Booked) {
                throw ValidationException::withMessages(['booking' => 'Pemesanan tidak dapat dibatalkan.']);
            }

            if (trim($reason) === '') {
                throw ValidationException::withMessages(['reason' => 'Alasan pembatalan wajib diisi.']);
            }

            $lockedBooking->update([
                'status' => GuidanceBookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancel_reason' => $reason,
            ]);

            return $lockedBooking->refresh();
        });
    }
}
