<?php

namespace App\Models;

use App\Enums\GuidanceBookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['guidance_slot_id', 'student_id', 'status', 'booked_at', 'cancelled_at', 'cancelled_by', 'cancel_reason', 'completed_at', 'lecturer_notes'])]
class GuidanceBooking extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => GuidanceBookingStatus::class, 'booked_at' => 'datetime', 'cancelled_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(GuidanceSlot::class, 'guidance_slot_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
