<?php

use App\Enums\UserRole;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('staff accounts cannot use the student portal', function () {
    $lecturer = User::factory()->create(['role' => UserRole::Lecturer]);

    $this->actingAs($lecturer)->get(route('dashboard'))->assertForbidden();
});

test('students do not need a verified email address to access the portal', function () {
    $student = User::factory()->unverified()->create();

    $this->actingAs($student)->get(route('dashboard'))->assertOk();
});

test('deactivated student sessions are invalidated on the next portal request', function () {
    $student = User::factory()->create(['is_active' => false]);

    $this->actingAs($student)->get(route('dashboard'))->assertRedirect(route('login'));

    $this->assertGuest();
});
