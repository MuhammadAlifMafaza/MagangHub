<?php

namespace Database\Factories;

use App\Models\Internship;
use App\Models\ReportPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportPeriod>
 */
class ReportPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'internship_id' => Internship::factory(),
            'week_number' => 1,
            'starts_on' => now()->addDays(7)->toDateString(),
            'ends_on' => now()->addDays(13)->toDateString(),
            'due_at' => now()->addDays(13)->setTime(23, 59, 59),
        ];
    }
}
