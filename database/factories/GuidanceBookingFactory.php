<?php

namespace Database\Factories;

use App\Enums\GuidanceBookingStatus;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSlot;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuidanceBooking>
 */
class GuidanceBookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guidance_slot_id' => GuidanceSlot::factory(),
            'student_id' => Student::factory(),
            'status' => GuidanceBookingStatus::Booked,
            'booked_at' => now(),
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancel_reason' => null,
            'completed_at' => null,
            'lecturer_notes' => null,
        ];
    }
}
