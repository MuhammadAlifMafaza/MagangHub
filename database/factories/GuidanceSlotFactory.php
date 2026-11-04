<?php

namespace Database\Factories;

use App\Enums\GuidanceSlotStatus;
use App\Models\GuidanceSlot;
use App\Models\Lecturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuidanceSlot>
 */
class GuidanceSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lecturer_id' => Lecturer::factory(),
            'starts_at' => now()->addDays(2)->setTime(9, 0),
            'ends_at' => now()->addDays(2)->setTime(9, 30),
            'capacity' => 1,
            'location' => fake()->randomElement(['Ruang A-201', 'https://meet.example.com/bimbingan']),
            'status' => GuidanceSlotStatus::Open,
            'cancelled_at' => null,
            'cancel_reason' => null,
        ];
    }
}
