<?php

namespace App\Actions;

use App\Enums\GuidanceSlotStatus;
use App\Models\GuidanceSlot;
use App\Models\Lecturer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateGuidanceSlot
{
    /** @param array{starts_at: string, ends_at: string, capacity?: int, location?: ?string} $data */
    public function handle(Lecturer $lecturer, array $data): GuidanceSlot
    {
        $start = now()->parse($data['starts_at']);
        $end = now()->parse($data['ends_at']);
        $duration = $start->diffInMinutes($end);
        $capacity = $data['capacity'] ?? config('magang.guidance.default_capacity');

        if ($start->isPast() || $end->lessThanOrEqualTo($start) || $duration < config('magang.guidance.minimum_duration_minutes') || $duration > config('magang.guidance.maximum_duration_minutes')) {
            throw ValidationException::withMessages(['starts_at' => 'Waktu slot harus di masa depan dan durasi harus sesuai batas yang ditentukan.']);
        }

        if ($capacity < 1 || $capacity > 255) {
            throw ValidationException::withMessages(['capacity' => 'Kapasitas slot harus antara 1 dan 255.']);
        }

        return DB::transaction(function () use ($lecturer, $data, $start, $end, $capacity): GuidanceSlot {
            $lockedLecturer = Lecturer::query()->lockForUpdate()->findOrFail($lecturer->id);
            $overlaps = $lockedLecturer->guidanceSlots()
                ->where('status', GuidanceSlotStatus::Open)
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start)
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages(['starts_at' => 'Jadwal bertumpang tindih dengan slot lain.']);
            }

            return $lockedLecturer->guidanceSlots()->create([
                ...$data,
                'starts_at' => $start,
                'ends_at' => $end,
                'capacity' => $capacity,
                'status' => GuidanceSlotStatus::Open,
            ]);
        });
    }
}
