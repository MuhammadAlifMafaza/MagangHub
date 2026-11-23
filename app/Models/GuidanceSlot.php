<?php

namespace App\Models;

use App\Enums\GuidanceBookingStatus;
use App\Enums\GuidanceSlotStatus;
use Database\Factories\GuidanceSlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $lecturer_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property int $capacity
 * @property int|string $slot_count
 * @property GuidanceSlotStatus $status
 * @property-read Lecturer $lecturer
 * @property-read HasMany<GuidanceBooking, $this> $bookings
 */
#[Fillable(['lecturer_id', 'starts_at', 'ends_at', 'capacity', 'location', 'status', 'cancelled_at', 'cancel_reason'])]
class GuidanceSlot extends Model
{
    /** @use HasFactory<GuidanceSlotFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'cancelled_at' => 'datetime', 'status' => GuidanceSlotStatus::class];
    }

    /** @return BelongsTo<Lecturer, $this> */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    /** @return HasMany<GuidanceBooking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(GuidanceBooking::class);
    }

    public function availabilityLabel(): string
    {
        if ($this->status === GuidanceSlotStatus::Cancelled) {
            return GuidanceSlotStatus::Cancelled->label();
        }

        if ($this->ends_at->lessThanOrEqualTo(now(config('magang.timezone')))) {
            return 'Selesai';
        }

        return $this->activeBookingCount() >= $this->capacity ? 'Penuh' : 'Tersedia';
    }

    public function availabilityColor(): string
    {
        if ($this->status === GuidanceSlotStatus::Cancelled) {
            return GuidanceSlotStatus::Cancelled->color();
        }

        if ($this->ends_at->lessThanOrEqualTo(now(config('magang.timezone'))) || $this->activeBookingCount() >= $this->capacity) {
            return 'neutral';
        }

        return 'success';
    }

    private function activeBookingCount(): int
    {
        if (array_key_exists('booked_count', $this->attributes)) {
            return (int) $this->attributes['booked_count'];
        }

        return $this->bookings()->where('status', '!=', GuidanceBookingStatus::Cancelled)->count();
    }
}
