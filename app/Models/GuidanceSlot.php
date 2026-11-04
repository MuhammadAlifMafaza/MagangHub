<?php

namespace App\Models;

use App\Enums\GuidanceBookingStatus;
use App\Enums\GuidanceSlotStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['lecturer_id', 'starts_at', 'ends_at', 'capacity', 'location', 'status', 'cancelled_at', 'cancel_reason'])]
class GuidanceSlot extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'cancelled_at' => 'datetime', 'status' => GuidanceSlotStatus::class];
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

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
