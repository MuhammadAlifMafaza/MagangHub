<?php

namespace App\Actions;

use App\Enums\GuidanceBookingStatus;
use App\Enums\GuidanceSlotStatus;
use App\Models\GuidanceSlot;
use App\Models\Lecturer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateGuidanceSlot
{
    /** @param array{starts_at: string, ends_at: string, capacity: int, location?: ?string} $data */
    public function handle(GuidanceSlot $slot, Lecturer $lecturer, array $data): GuidanceSlot
    {
        return DB::transaction(function () use ($slot, $lecturer, $data): GuidanceSlot {
            $lockedLecturer = Lecturer::query()->lockForUpdate()->findOrFail($lecturer->id);
            $lockedSlot = $lockedLecturer->guidanceSlots()->whereKey($slot->id)->lockForUpdate()->firstOrFail();

            if ($lockedSlot->status !== GuidanceSlotStatus::Open) {
                throw ValidationException::withMessages(['slot' => 'Slot yang sudah dibatalkan tidak dapat diubah.']);
            }

            $start = now()->parse($data['starts_at']);
            $end = now()->parse($data['ends_at']);
            $duration = $start->diffInMinutes($end);
            $activeBookings = $lockedSlot->bookings()->where('status', GuidanceBookingStatus::Booked)->count();
            $occupied = $lockedSlot->bookings()->where('status', '!=', GuidanceBookingStatus::Cancelled)->count();
            $timeChanged = ! $lockedSlot->starts_at->equalTo($start) || ! $lockedSlot->ends_at->equalTo($end);

            if ($data['capacity'] < 1 || $data['capacity'] > 255 || $data['capacity'] < $occupied) {
                throw ValidationException::withMessages(['capacity' => 'Kapasitas tidak boleh di bawah jumlah pemesanan dan harus antara 1 dan 255.']);
            }

            if ($timeChanged && ($start->isPast() || $end->lessThanOrEqualTo($start) || $duration < config('magang.guidance.minimum_duration_minutes') || $duration > config('magang.guidance.maximum_duration_minutes'))) {
                throw ValidationException::withMessages(['starts_at' => 'Waktu harus di masa depan dan durasi harus sesuai batas yang ditentukan.']);
            }

            if ($timeChanged && $activeBookings > 0) {
                throw ValidationException::withMessages(['starts_at' => 'Waktu tidak dapat diubah setelah slot dipesan dan kapasitas tidak boleh di bawah jumlah pemesanan aktif.']);
            }

            $overlaps = $timeChanged && $lockedLecturer->guidanceSlots()
                ->where('id', '!=', $lockedSlot->id)
                ->where('status', GuidanceSlotStatus::Open)
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start)
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages(['starts_at' => 'Jadwal bertumpang tindih dengan slot lain.']);
            }

            $lockedSlot->update([...$data, 'starts_at' => $start, 'ends_at' => $end]);

            return $lockedSlot->refresh();
        });
    }
}
