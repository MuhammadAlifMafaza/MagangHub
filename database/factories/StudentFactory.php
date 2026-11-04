<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::Student]),
            'nim' => fake()->unique()->numerify('##.###.####'),
            'study_program' => fake()->randomElement(['Informatika', 'Sistem Informasi']),
            'cohort_year' => fake()->numberBetween(2018, 2026),
            'phone' => fake()->numerify('08##########'),
        ];
    }
}
