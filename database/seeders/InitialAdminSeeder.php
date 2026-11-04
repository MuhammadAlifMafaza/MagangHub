<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class InitialAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $initialAdmin = config('magang.initial_admin');

        if (blank($initialAdmin['name']) || blank($initialAdmin['email']) || blank($initialAdmin['password'])) {
            return;
        }

        $admin = User::query()->firstOrNew(['email' => $initialAdmin['email']]);

        if ($admin->exists) {
            return;
        }

        $admin->forceFill([
            'name' => $initialAdmin['name'],
            'password' => $initialAdmin['password'],
            'role' => UserRole::Admin,
            'is_active' => true,
            'must_change_password' => true,
        ])->save();
    }
}
