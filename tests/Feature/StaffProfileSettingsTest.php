<?php

use App\Enums\UserRole;
use App\Filament\Dosen\Pages\Profile as LecturerProfile;
use App\Filament\Pages\Profile as AdminProfile;
use App\Models\Lecturer;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('lecturers have a role-specific editable profile and settings page', function () {
    $lecturer = Lecturer::factory()->create([
        'department' => 'Informatika',
        'phone' => '08123456789',
    ]);
    $this->actingAs($lecturer->user);

    $this->get(route('filament.dosen.pages.profile'))
        ->assertOk()
        ->assertSee('Profil dosen')
        ->assertSee('NIDN');
    $this->get(route('filament.dosen.pages.settings'))
        ->assertOk()
        ->assertSee('Pengaturan akun dosen')
        ->assertSee('Pemeriksaan laporan');
    $this->get('/dosen')
        ->assertOk()
        ->assertSee($lecturer->user->name)
        ->assertSeeInOrder([$lecturer->user->name, route('filament.dosen.pages.profile')], false)
        ->assertSee(route('filament.dosen.pages.profile'), false)
        ->assertSee(route('filament.dosen.pages.settings'), false);

    Filament::setCurrentPanel('dosen');

    Livewire::test(LecturerProfile::class)
        ->set('name', 'Dosen Baru')
        ->set('email', 'dosen.baru@example.com')
        ->set('department', 'Sistem Informasi')
        ->set('phone', '08111111111')
        ->call('save')
        ->assertHasNoErrors();

    expect($lecturer->fresh()->department)->toBe('Sistem Informasi')
        ->and($lecturer->fresh()->phone)->toBe('08111111111')
        ->and($lecturer->user->fresh()->name)->toBe('Dosen Baru')
        ->and($lecturer->user->fresh()->email)->toBe('dosen.baru@example.com');
});

test('administrators have separate profile and system settings pages', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin);

    $this->get(route('filament.admin.pages.profile'))
        ->assertOk()
        ->assertSee('Profil administrator')
        ->assertSee('Administrator');
    $this->get(route('filament.admin.pages.settings'))
        ->assertOk()
        ->assertSee('Pengaturan administrator')
        ->assertSee('Akun pengguna')
        ->assertSee('Rekap administrasi');
    $this->get('/admin')
        ->assertOk()
        ->assertSee($admin->name)
        ->assertSeeInOrder([$admin->name, route('filament.admin.pages.profile')], false)
        ->assertSee(route('filament.admin.pages.profile'), false)
        ->assertSee(route('filament.admin.pages.settings'), false);

    Filament::setCurrentPanel('admin');

    Livewire::test(AdminProfile::class)
        ->set('name', 'Admin Baru')
        ->set('email', 'admin.baru@example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect($admin->fresh()->name)->toBe('Admin Baru')
        ->and($admin->fresh()->email)->toBe('admin.baru@example.com');
});

test('staff profile pages are restricted to the matching role', function () {
    $student = User::factory()->create(['role' => UserRole::Student]);

    $this->actingAs($student)
        ->get(route('filament.dosen.pages.profile'))
        ->assertForbidden();
    $this->get(route('filament.admin.pages.settings'))
        ->assertForbidden();
});
