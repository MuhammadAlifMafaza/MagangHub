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
use Illuminate\Validation\Validator as ValidatorInstance;

class UpdateAccount
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, User $account, array $data): User
    {
        Gate::forUser($actor)->authorize('update', $account);

        $account->loadMissing(['student', 'lecturer']);
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($account->id)],
        ];

        if ($account->role === UserRole::Student) {
            $rules += [
                'nim' => ['required', 'string', 'max:20', Rule::unique('students', 'nim')->ignore($account->student?->id)],
                'study_program' => ['required', 'string', 'max:100'],
                'cohort_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
                'phone' => ['nullable', 'string', 'max:30'],
            ];
        } elseif ($account->role === UserRole::Lecturer) {
            $rules += [
                'nidn' => ['required', 'string', 'max:20', Rule::unique('lecturers', 'nidn')->ignore($account->lecturer?->id)],
                'department' => ['nullable', 'string', 'max:100'],
                'phone' => ['nullable', 'string', 'max:30'],
            ];
        }

        $validated = Validator::make($data, $rules, [
            'email.unique' => 'Alamat email sudah digunakan.',
            'nim.unique' => 'NIM sudah digunakan.',
            'nidn.unique' => 'NIDN sudah digunakan.',
        ])->after(function (ValidatorInstance $validator) use ($account, $data): void {
            if ($account->role === UserRole::Student && filled($data['nim'] ?? null) && Lecturer::query()->where('nidn', $data['nim'])->exists()) {
                $validator->errors()->add('nim', 'NIM sudah digunakan sebagai NIDN akun dosen.');
            }

            if ($account->role === UserRole::Lecturer && filled($data['nidn'] ?? null) && Student::query()->where('nim', $data['nidn'])->exists()) {
                $validator->errors()->add('nidn', 'NIDN sudah digunakan sebagai NIM akun mahasiswa.');
            }
        })->validate();

        return DB::transaction(function () use ($account, $validated): User {
            $lockedAccount = User::query()->lockForUpdate()->findOrFail($account->id);
            $lockedAccount->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            if ($lockedAccount->role === UserRole::Student) {
                $profile = $lockedAccount->student()->firstOrFail();
                $profile->fill([
                    'nim' => $validated['nim'],
                    'study_program' => $validated['study_program'],
                    'cohort_year' => $validated['cohort_year'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                ])->saveOrFail();
            } elseif ($lockedAccount->role === UserRole::Lecturer) {
                $profile = $lockedAccount->lecturer()->firstOrFail();
                $profile->fill([
                    'nidn' => $validated['nidn'] ?? null,
                    'department' => $validated['department'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                ])->saveOrFail();
            }

            return $lockedAccount->refresh();
        });
    }
}
