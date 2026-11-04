<?php

namespace Database\Factories;

use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use App\Models\Lecturer;
use App\Models\WeeklyReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Assessment> */
class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    public function definition(): array
    {
        return [
            'weekly_report_id' => WeeklyReport::factory(), 'lecturer_id' => Lecturer::factory(),
            'score' => fake()->randomFloat(2, 0, 100), 'notes' => fake()->sentence(),
            'status' => AssessmentStatus::Draft, 'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => AssessmentStatus::Published, 'published_at' => now()]);
    }
}
