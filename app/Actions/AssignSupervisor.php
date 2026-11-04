<?php

namespace App\Actions;

use App\Enums\SupervisorAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignSupervisor
{
    public function handle(Student $student, Lecturer $lecturer, User $assignedBy): SupervisorAssignment
    {
        return DB::transaction(function () use ($student, $lecturer, $assignedBy): SupervisorAssignment {
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->id);
            $activeLecturer = Lecturer::query()
                ->whereKey($lecturer->id)
                ->whereHas('user', fn ($query) => $query
                    ->where('role', UserRole::Lecturer)
                    ->where('is_active', true))
                ->exists();

            if (! $activeLecturer) {
                throw ValidationException::withMessages([
                    'lecturer_id' => 'Dosen harus memiliki akun aktif untuk menerima penugasan.',
                ]);
            }

            if ($lockedStudent->supervisorAssignments()->where('status', SupervisorAssignmentStatus::Active)->exists()) {
                throw ValidationException::withMessages([
                    'student_id' => 'Mahasiswa sudah memiliki dosen pembimbing aktif. Akhiri penugasan lama terlebih dahulu.',
                ]);
            }

            return $lockedStudent->supervisorAssignments()->create([
                'lecturer_id' => $lecturer->id,
                'status' => SupervisorAssignmentStatus::Active,
                'assigned_by' => $assignedBy->id,
                'assigned_at' => now(config('magang.timezone')),
            ]);
        });
    }
}
