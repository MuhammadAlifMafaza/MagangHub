<?php

use App\Enums\UserRole;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('Masuk sebagai mahasiswa')
        ->assertSee('images/brand/Logo.svg')
        ->assertDontSee('Masuk ke portal staff');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('students can authenticate using their NIM', function () {
    $student = Student::factory()->create();

    $this->post(route('login.store'), [
        'email' => $student->nim,
        'password' => 'password',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($student->user);
});

test('lecturers can authenticate using their NIDN', function () {
    $lecturer = Lecturer::factory()->create();

    $this->post(route('login.store'), [
        'email' => $lecturer->nidn,
        'password' => 'password',
    ])->assertSessionHasNoErrors()
        ->assertRedirect('/dosen');

    $this->assertAuthenticatedAs($lecturer->user);
});

test('ambiguous login identifiers are rejected', function () {
    $student = Student::factory()->create();
    User::factory()->create(['email' => $student->nim]);

    $this->post(route('login.store'), [
        'email' => $student->nim,
        'password' => 'password',
    ])->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('landing page shows the sign-in action to guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Masuk', false)
        ->assertSee('images/brand/Logo.svg', false)
        ->assertSee('apple-touch-icon.png', false);
});

test('landing page shows the role-specific panel link and logout action to authenticated users', function (UserRole $role, string $panelUrl) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get(route('home'))
        ->assertOk()
        ->assertSee('Buka panel', false)
        ->assertSee($panelUrl, false)
        ->assertSee('Keluar', false)
        ->assertSee($user->initials());
})->with([
    'student' => [UserRole::Student, '/mahasiswa'],
    'lecturer' => [UserRole::Lecturer, '/dosen'],
    'admin' => [UserRole::Admin, '/admin'],
]);

test('staff login redirects to the correct Filament panel', function (UserRole $role, string $destination) {
    $user = User::factory()->create(['role' => $role]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors()
        ->assertRedirect($destination);

    $this->assertAuthenticatedAs($user);
})->with([
    'admin' => [UserRole::Admin, '/admin'],
    'lecturer' => [UserRole::Lecturer, '/dosen'],
]);

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('inactive accounts cannot authenticate', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});
