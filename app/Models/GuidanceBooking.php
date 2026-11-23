<?php

namespace App\Models;

use App\Enums\GuidanceBookingStatus;
use Database\Factories\GuidanceBookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $guidance_slot_id
 * @property int $student_id
 * @property GuidanceBookingStatus $status
 * @property int|string $booked_count
 * @property int|string $completed_count
 * @property int|string $no_show_count
 * @property Carbon|null $cancelled_at
 * @property-read GuidanceSlot $slot
 * @property-read Student $student
 */
#[Fillable(['guidance_slot_id', 'student_id', 'status', 'booked_at', 'cancelled_at', 'cancelled_by', 'cancel_reason', 'completed_at', 'lecturer_notes'])]
class GuidanceBooking extends Model
{
    /** @use HasFactory<GuidanceBookingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => GuidanceBookingStatus::class, 'booked_at' => 'datetime', 'cancelled_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    /** @return BelongsTo<GuidanceSlot, $this> */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(GuidanceSlot::class, 'guidance_slot_id');
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<User, $this> */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
