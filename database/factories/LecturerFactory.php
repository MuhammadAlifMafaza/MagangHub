<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lecturer>
 */
class LecturerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::Lecturer]),
            'nidn' => fake()->unique()->numerify('##########'),
            'department' => fake()->randomElement(['Informatika', 'Sistem Informasi']),
            'phone' => fake()->numerify('08##########'),
        ];
    }
}
