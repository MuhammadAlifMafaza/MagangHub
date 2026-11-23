<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\InitialAdminSeeder;
use Illuminate\Support\Facades\Hash;

test('initial admin seeder does not create an account without environment credentials', function () {
    config()->set('magang.initial_admin', [
        'name' => null,
        'email' => null,
        'password' => null,
    ]);

    $this->seed(InitialAdminSeeder::class);

    $this->assertDatabaseCount('users', 0);
});

test('initial admin seeder creates an admin who must change their password', function () {
    config()->set('magang.initial_admin', [
        'name' => 'Admin MagangHub',
        'email' => 'admin@example.test',
        'password' => 'temporary-secret',
    ]);

    $this->seed(InitialAdminSeeder::class);

    $admin = User::query()->where('email', 'admin@example.test')->firstOrFail();

    expect($admin->name)->toBe('Admin MagangHub')
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and($admin->is_active)->toBeTrue()
        ->and($admin->must_change_password)->toBeTrue()
        ->and(Hash::check('temporary-secret', $admin->password))->toBeTrue();
});

test('initial admin seeder does not overwrite an existing account', function () {
    $admin = User::factory()->create([
        'name' => 'Existing Admin',
        'email' => 'admin@example.test',
        'role' => UserRole::Admin,
    ]);
    config()->set('magang.initial_admin', [
        'name' => 'Replacement Name',
        'email' => 'admin@example.test',
        'password' => 'replacement-secret',
    ]);

    $this->seed(InitialAdminSeeder::class);

    expect($admin->fresh()->name)->toBe('Existing Admin')
        ->and(Hash::check('password', $admin->fresh()->password))->toBeTrue();
    $this->assertDatabaseCount('users', 1);
});
