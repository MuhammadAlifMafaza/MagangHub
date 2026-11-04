<?php

namespace Database\Factories;

use App\Enums\SupervisorAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupervisorAssignment>
 */
class SupervisorAssignmentFactory extends Factory
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
            'lecturer_id' => Lecturer::factory(),
            'status' => SupervisorAssignmentStatus::Active,
            'assigned_by' => User::factory()->state(['role' => UserRole::Admin]),
            'assigned_at' => now(),
            'ended_at' => null,
            'end_reason' => null,
        ];
    }
}
