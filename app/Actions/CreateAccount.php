<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator as ValidatorInstance;

class CreateAccount
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $createdBy, array $data): User
    {
        Gate::forUser($createdBy)->authorize('create', User::class);

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::default()],
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'nim' => ['required_if:role,student', 'nullable', 'string', 'max:20', Rule::unique('students', 'nim')],
            'study_program' => ['required_if:role,student', 'nullable', 'string', 'max:100'],
            'cohort_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'nidn' => ['required_if:role,lecturer', 'nullable', 'string', 'max:20', Rule::unique('lecturers', 'nidn')],
            'department' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ])->after(function (ValidatorInstance $validator) use ($data): void {
            $role = UserRole::tryFrom((string) ($data['role'] ?? ''));

            if ($role === UserRole::Student && filled($data['nim'] ?? null) && Lecturer::query()->where('nidn', $data['nim'])->exists()) {
                $validator->errors()->add('nim', 'NIM sudah digunakan sebagai NIDN akun dosen.');
            }

            if ($role === UserRole::Lecturer && filled($data['nidn'] ?? null) && Student::query()->where('nim', $data['nidn'])->exists()) {
                $validator->errors()->add('nidn', 'NIDN sudah digunakan sebagai NIM akun mahasiswa.');
            }
        })->validate();

        $role = UserRole::from($validated['role']);

        return DB::transaction(function () use ($validated, $role): User {
            $user = new User;
            $user->forceFill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $role,
                'is_active' => true,
                'must_change_password' => true,
            ])->save();

            if ($role === UserRole::Student) {
                $user->student()->create([
                    'nim' => $validated['nim'],
                    'study_program' => $validated['study_program'],
                    'cohort_year' => $validated['cohort_year'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                ]);
            } elseif ($role === UserRole::Lecturer) {
                $user->lecturer()->create([
                    'nidn' => $validated['nidn'],
                    'department' => $validated['department'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                ]);
            }

            return $user;
        });
    }
}
