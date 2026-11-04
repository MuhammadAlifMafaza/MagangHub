<?php

namespace App\Livewire;

use App\Actions\BookGuidanceSlot;
use App\Actions\CancelGuidanceBooking;
use App\Enums\GuidanceBookingStatus;
use App\Enums\GuidanceSlotStatus;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSlot;
use App\Models\Lecturer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class StudentGuidance extends Component
{
    public ?int $bookingToConfirm = null;

    public ?int $bookingToCancel = null;

    public string $cancelReason = '';

    public function confirmBooking(int $slotId): void
    {
        $this->bookingToConfirm = $slotId;
    }

    public function book(BookGuidanceSlot $action): void
    {
        $slotId = $this->bookingToConfirm;
        abort_unless($slotId !== null, 404);
        $student = Auth::user()?->student;
        abort_unless($student, 403);

        $lecturer = $student->activeSupervisorAssignment()->with('lecturer')->first()?->lecturer;
        abort_unless($lecturer instanceof Lecturer, 403);

        $slot = GuidanceSlot::query()->where('lecturer_id', $lecturer->id)
            ->where('status', GuidanceSlotStatus::Open)->findOrFail($slotId);

        try {
            $action->handle($student, $slot);
            $this->bookingToConfirm = null;
            session()->flash('guidance-success', 'Jadwal bimbingan berhasil dipesan.');
        } catch (ValidationException $exception) {
            $this->addError('slot', $exception->errors()['slot'][0] ?? 'Jadwal tidak dapat dipesan.');
        }
    }

    public function cancel(int $bookingId, CancelGuidanceBooking $action): void
    {
        $student = Auth::user()?->student;
        abort_unless($student, 403);

        $this->validate(['cancelReason' => ['nullable', 'string', 'max:1000']]);
        $booking = $student->guidanceBookings()->whereKey($bookingId)->firstOrFail();

        try {
            $action->handle($booking, $student, $this->cancelReason ?: null);
            $this->reset(['bookingToCancel', 'cancelReason']);
            session()->flash('guidance-success', 'Pemesanan berhasil dibatalkan.');
        } catch (ValidationException $exception) {
            $this->addError('booking', $exception->errors()['booking'][0] ?? 'Pemesanan tidak dapat dibatalkan.');
        }
    }

    public function render(): mixed
    {
        $student = Auth::user()?->student;
        abort_unless($student, 403);

        $assignment = $student->activeSupervisorAssignment()->with('lecturer.user')->first();
        $lecturer = $assignment?->lecturer;

        $availableSlots = $lecturer
            ? GuidanceSlot::query()->with('lecturer.user')->withCount(['bookings as booked_count' => fn (Builder $query) => $query->where('status', '!=', GuidanceBookingStatus::Cancelled)])
                ->where('lecturer_id', $lecturer->id)->where('status', GuidanceSlotStatus::Open)
                ->where('starts_at', '>', now())->orderBy('starts_at')->get()
            : collect();

        $bookings = $student->guidanceBookings()->with('slot.lecturer.user')->latest('booked_at')->get();
        $hasUpcomingBooking = $bookings->contains(fn (GuidanceBooking $booking) => $booking->status === GuidanceBookingStatus::Booked && $booking->slot->starts_at->isFuture());
        $bookingCutoff = now()->addHours(config('magang.guidance.booking_cutoff_hours'));
        $cancellationCutoff = now()->addHours(config('magang.guidance.cancellation_cutoff_hours'));

        return view('livewire.student-guidance', compact('availableSlots', 'bookings', 'hasUpcomingBooking', 'lecturer', 'bookingCutoff', 'cancellationCutoff'))
            ->layout('layouts.app');
    }
}
