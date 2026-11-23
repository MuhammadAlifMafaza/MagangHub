<?php

use App\Actions\CreateAccount;
use App\Actions\ResetAccountPassword;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

test('admin account creation creates the matching profile and requires a password change', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $student = app(CreateAccount::class)->handle($admin, [
        'name' => 'Mahasiswa Uji',
        'email' => 'mahasiswa@example.test',
        'password' => 'temporary-password',
        'role' => UserRole::Student->value,
        'nim' => '23.230.0035',
        'study_program' => 'Informatika',
        'cohort_year' => 2023,
        'phone' => '081234567890',
    ]);

    expect($student->role)->toBe(UserRole::Student)
        ->and($student->is_active)->toBeTrue()
        ->and($student->must_change_password)->toBeTrue()
        ->and($student->student->nim)->toBe('23.230.0035')
        ->and(Hash::check('temporary-password', $student->password))->toBeTrue();
});

test('admin account creation requires a lecturer NIDN', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    expect(fn () => app(CreateAccount::class)->handle($admin, [
        'name' => 'Dosen Uji',
        'email' => 'dosen@example.test',
        'password' => 'temporary-password',
        'role' => UserRole::Lecturer->value,
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseMissing('users', ['email' => 'dosen@example.test']);
});

test('admin account creation rejects identifiers already used by another staff role', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create(['nim' => '1234567890']);

    expect(fn () => app(CreateAccount::class)->handle($admin, [
        'name' => 'Dosen Uji',
        'email' => 'dosen@example.test',
        'password' => 'temporary-password',
        'role' => UserRole::Lecturer->value,
        'nidn' => $student->nim,
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseMissing('users', ['email' => 'dosen@example.test']);
});

test('non-admins cannot create accounts through the account action', function () {
    $student = User::factory()->create(['role' => UserRole::Student]);

    expect(fn () => app(CreateAccount::class)->handle($student, [
        'name' => 'Akun Terlarang',
        'email' => 'forbidden@example.test',
        'password' => 'temporary-password',
        'role' => UserRole::Student->value,
        'nim' => '23.230.0036',
        'study_program' => 'Informatika',
    ]))->toThrow(AuthorizationException::class);

    $this->assertDatabaseMissing('users', ['email' => 'forbidden@example.test']);
});

test('admin password reset marks the account as requiring a password change', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = User::factory()->create(['role' => UserRole::Student]);

    app(ResetAccountPassword::class)->handle($admin, $student, 'new-temporary-password');

    expect($student->fresh()->must_change_password)->toBeTrue()
        ->and(Hash::check('new-temporary-password', $student->fresh()->password))->toBeTrue();
});

test('user administration is available only in the admin panel', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $lecturer = User::factory()->create(['role' => UserRole::Lecturer]);

    $this->actingAs($admin)->get('/admin/users')->assertOk();
    $this->actingAs($lecturer)->get('/admin/users')->assertForbidden();
});

test('protected report routes require an active account and password update', function () {
    $inactiveLecturer = User::factory()->create([
        'role' => UserRole::Lecturer,
        'is_active' => false,
    ]);

    $this->actingAs($inactiveLecturer)
        ->get('/dosen/laporan/antrean')
        ->assertForbidden();

    $temporaryPasswordLecturer = User::factory()->create([
        'role' => UserRole::Lecturer,
        'must_change_password' => true,
    ]);

    $this->actingAs($temporaryPasswordLecturer)
        ->get('/dosen/laporan/antrean')
        ->assertRedirect(route('security.edit'));
});
