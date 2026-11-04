<?php

namespace App\Actions;

use App\Enums\SupervisorAssignmentStatus;
use App\Enums\UserRole;
use App\Models\GuidanceBooking;
use App\Models\Student;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EndSupervisorAssignment
{
    public function __construct(private CancelGuidanceBookingByStaff $cancelBooking) {}

    public function handle(SupervisorAssignment $assignment, User $endedBy, string $reason): SupervisorAssignment
    {
        if ($endedBy->role !== UserRole::Admin || ! $endedBy->is_active) {
            abort(403);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['end_reason' => 'Alasan mengakhiri penugasan wajib diisi.']);
        }

        return DB::transaction(function () use ($assignment, $endedBy, $reason): SupervisorAssignment {
            Student::query()->lockForUpdate()->findOrFail($assignment->student_id);
            $lockedAssignment = SupervisorAssignment::query()
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAssignment->status !== SupervisorAssignmentStatus::Active) {
                throw ValidationException::withMessages([
                    'assignment' => 'Hanya penugasan aktif yang dapat diakhiri.',
                ]);
            }

            $lockedAssignment->forceFill([
                'status' => SupervisorAssignmentStatus::Ended,
                'ended_at' => now(config('magang.timezone')),
                'end_reason' => $reason,
            ])->save();

            $upcomingBookings = GuidanceBooking::query()
                ->where('student_id', $lockedAssignment->student_id)
                ->where('status', 'booked')
                ->whereHas('slot', fn ($query) => $query
                    ->where('lecturer_id', $lockedAssignment->lecturer_id)
                    ->where('starts_at', '>', now(config('magang.timezone'))))
                ->orderBy('id')
                ->get();

            foreach ($upcomingBookings as $booking) {
                $this->cancelBooking->handle($booking, $endedBy, $reason);
            }

            return $lockedAssignment;
        });
    }
}
