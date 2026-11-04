<?php

use App\Enums\UserRole;
use App\Models\User;
use Filament\Panel;

test('users cast roles and account access flags to their declared types', function () {
    $user = User::factory()->create();

    expect($user->fresh()->role)->toBe(UserRole::Student)
        ->and($user->fresh()->is_active)->toBeTrue()
        ->and($user->fresh()->must_change_password)->toBeFalse();
});

test('user accounts can have a student or lecturer profile', function () {
    $studentUser = User::factory()->create();
    $lecturerUser = User::factory()->create();

    $studentUser->student()->create([
        'nim' => '23.230.0035',
        'study_program' => 'Informatika',
    ]);
    $lecturerUser->lecturer()->create([
        'nidn' => '1234567890',
        'department' => 'Informatika',
    ]);

    expect($studentUser->fresh()->student->nim)->toBe('23.230.0035')
        ->and($lecturerUser->fresh()->lecturer->nidn)->toBe('1234567890');
});

test('staff access only the panel matching their role', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $lecturer = User::factory()->create(['role' => UserRole::Lecturer]);
    $student = User::factory()->create(['role' => UserRole::Student]);

    expect($admin->canAccessPanel(Panel::make()->id('admin')))->toBeTrue()
        ->and($admin->canAccessPanel(Panel::make()->id('dosen')))->toBeFalse()
        ->and($lecturer->canAccessPanel(Panel::make()->id('dosen')))->toBeTrue()
        ->and($lecturer->canAccessPanel(Panel::make()->id('admin')))->toBeFalse()
        ->and($student->canAccessPanel(Panel::make()->id('admin')))->toBeFalse()
        ->and($student->canAccessPanel(Panel::make()->id('dosen')))->toBeFalse();
});

test('inactive staff cannot access a Filament panel', function () {
    $admin = User::factory()->create([
        'role' => UserRole::Admin,
        'is_active' => false,
    ]);

    expect($admin->canAccessPanel(Panel::make()->id('admin')))->toBeFalse();
});
