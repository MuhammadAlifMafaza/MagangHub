<?php

namespace Database\Factories;

use App\Enums\InternshipStatus;
use App\Models\Internship;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Internship>
 */
class InternshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'company_name' => fake()->company(),
            'company_supervisor_name' => fake()->name(),
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'status' => InternshipStatus::Active,
        ];
    }
}
