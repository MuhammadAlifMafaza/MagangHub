<?php

use App\Enums\UserRole;
use App\Filament\Auth\StaffLogin;
use App\Models\Lecturer;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
test('admins can access only the admin panel', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get('/admin')->assertOk();
    $this->actingAs($admin)->get('/dosen')->assertForbidden();
});

test('staff required to change their password are redirected out of the panel', function () {
    $admin = User::factory()->create([
        'role' => UserRole::Admin,
        'must_change_password' => true,
    ]);

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/admin')
        ->assertRedirect(route('security.edit'));
});

test('lecturers can access only the lecturer panel', function () {
    $lecturer = User::factory()->create(['role' => UserRole::Lecturer]);

    $this->actingAs($lecturer)->get('/dosen')->assertOk();
    $this->actingAs($lecturer)->get('/admin')->assertForbidden();
});

test('students cannot access staff panels', function () {
    $student = User::factory()->create(['role' => UserRole::Student]);

    $this->actingAs($student)->get('/admin')->assertForbidden();
    $this->actingAs($student)->get('/dosen')->assertForbidden();
});

test('guests are directed to the appropriate staff panel login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->get('/dosen')->assertRedirect('/dosen/login');
});

test('staff panel login routes render the custom Filament login page', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Masuk Panel Admin')
        ->assertSee('Alamat email')
        ->assertSee('images/brand/Logo.svg')
        ->assertDontSee('portal mahasiswa');

    $this->get('/dosen/login')
        ->assertOk()
        ->assertSee('Masuk Panel Dosen')
        ->assertSee('Email atau NIDN')
        ->assertDontSee('portal mahasiswa');
});

test('lecturers can authenticate into the Filament panel using their NIDN', function () {
    $lecturer = Lecturer::factory()->create();

    Filament::setCurrentPanel('dosen');

    Livewire::test(StaffLogin::class)
        ->fillForm([
            'email' => $lecturer->nidn,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertRedirect('/dosen');

    $this->assertAuthenticatedAs($lecturer->user);
});

test('administrators can authenticate into the Filament panel using their email', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Filament::setCurrentPanel('admin');

    Livewire::test(StaffLogin::class)
        ->fillForm([
            'email' => $admin->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($admin);
});

test('students cannot authenticate through a staff panel login', function () {
    $student = User::factory()->create(['role' => UserRole::Student]);

    Filament::setCurrentPanel('admin');

    Livewire::test(StaffLogin::class)
        ->fillForm([
            'email' => $student->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});
