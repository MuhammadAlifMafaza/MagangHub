<?php

namespace App\Actions;

use App\Enums\GuidanceBookingStatus;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSlot;
use App\Models\Lecturer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordGuidanceBookingOutcome
{
    public function handle(GuidanceBooking $booking, Lecturer $lecturer, GuidanceBookingStatus $outcome, ?string $notes = null): GuidanceBooking
    {
        if (! in_array($outcome, [GuidanceBookingStatus::Completed, GuidanceBookingStatus::NoShow], true)) {
            throw ValidationException::withMessages(['status' => 'Hasil bimbingan tidak valid.']);
        }

        $updatedBooking = DB::transaction(function () use ($booking, $lecturer, $outcome, $notes): GuidanceBooking {
            $slot = GuidanceSlot::query()->whereKey($booking->guidance_slot_id)->lockForUpdate()->firstOrFail();
            $lockedBooking = GuidanceBooking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($slot->lecturer_id !== $lecturer->id || $lockedBooking->status !== GuidanceBookingStatus::Booked || $slot->starts_at->isFuture()) {
                throw ValidationException::withMessages(['booking' => 'Pemesanan belum dapat dicatat hasilnya.']);
            }

            $lockedBooking->update(['status' => $outcome, 'completed_at' => now(), 'lecturer_notes' => $notes]);

            return $lockedBooking->refresh();
        });

        $updatedBooking->load('student.user', 'slot');
        $outcomeText = $outcome === GuidanceBookingStatus::Completed ? 'Selesai' : 'Tidak hadir';
        app(SendWorkflowNotification::class)->handle(
            $updatedBooking->student->user,
            'guidance_outcome_recorded',
            'Hasil bimbingan dicatat',
            'Dosen mencatat hasil bimbingan Anda: '.$outcomeText.'.'.($notes ? ' Catatan: '.$notes : ''),
            route('guidance.student', absolute: false),
            'Lihat jadwal',
        );

        return $updatedBooking;
    }
}
