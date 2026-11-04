<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateAccount
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $createdBy, array $data): User
    {
        Gate::forUser($createdBy)->authorize('create', User::class);

        $role = UserRole::tryFrom($data['role'] ?? '');

        if ($role === null) {
            throw ValidationException::withMessages(['role' => 'Peran akun tidak valid.']);
        }

        return DB::transaction(function () use ($data, $role): User {
            $user = new User;
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $role,
                'is_active' => true,
                'must_change_password' => true,
            ])->save();

            if ($role === UserRole::Student) {
                $user->student()->create([
                    'nim' => $data['nim'],
                    'study_program' => $data['study_program'],
                    'cohort_year' => $data['cohort_year'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ]);
            } elseif ($role === UserRole::Lecturer) {
                $user->lecturer()->create([
                    'nidn' => $data['nidn'] ?? null,
                    'department' => $data['department'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ]);
            }

            return $user;
        });
    }
}
