<?php

use App\Enums\UserRole;
use App\Mail\PasswordResetCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());
});

test('custom login and OTP recovery screens can be rendered', function () {
    $this->get(route('login'))->assertOk()->assertSee('Masuk sebagai mahasiswa');
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Kirim kode verifikasi')
        ->assertSee('Pemulihan akun mahasiswa')
        ->assertDontSee('Masuk sebagai staff');

    $this->get(route('password.request', ['audience' => 'staff', 'panel' => 'dosen']))
        ->assertOk()
        ->assertSee('Pemulihan akun staff')
        ->assertDontSee('portal mahasiswa');
});

test('staff password recovery returns to the same staff panel', function () {
    Mail::fake();
    $user = User::factory()->create(['role' => UserRole::Lecturer]);

    $this->post(route('password.otp.send', ['audience' => 'staff']), [
        'email' => $user->email,
        'panel' => 'dosen',
    ]);

    Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $mail): bool {
        $this->post(route('password.otp.reset', ['audience' => 'staff']), [
            'code' => $mail->code,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect('/dosen/login');

        return true;
    });
});

test('password recovery does not issue OTPs across student and staff audiences', function () {
    Mail::fake();
    $student = User::factory()->create(['role' => UserRole::Student]);
    $staff = User::factory()->create(['role' => UserRole::Admin]);

    $this->post(route('password.otp.send', ['audience' => 'staff']), ['email' => $student->email])
        ->assertRedirect(route('password.otp.verify', ['audience' => 'staff']))
        ->assertSessionHas('status', 'Jika email terdaftar, kode verifikasi akan dikirimkan. Periksa kotak masuk Anda.');

    $this->post(route('password.otp.send', ['audience' => 'student']), ['email' => $staff->email])
        ->assertRedirect(route('password.otp.verify', ['audience' => 'student']))
        ->assertSessionHas('status', 'Jika email terdaftar, kode verifikasi akan dikirimkan. Periksa kotak masuk Anda.');

    Mail::assertNothingSent();
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $student->email]);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $staff->email]);
});

test('OTP from one audience cannot be verified through the other audience route', function () {
    Mail::fake();
    $student = User::factory()->create(['role' => UserRole::Student]);
    $originalPassword = $student->password;

    $this->post(route('password.otp.send', ['audience' => 'student']), ['email' => $student->email]);

    Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $mail) use ($student): bool {
        $this->post(route('password.otp.reset', ['audience' => 'staff']), [
            'code' => $mail->code,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('password.request', ['audience' => 'student']));

        expect($student->fresh()->password)->toBe($student->password);

        return true;
    });

    expect($student->fresh()->password)->toBe($originalPassword);
});

test('password reset code is mailed to the requested account', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->post(route('password.otp.send', ['audience' => 'student']), ['email' => $user->email])
        ->assertRedirect(route('password.otp.verify', ['audience' => 'student']));

    Mail::assertSent(PasswordResetCode::class, fn (PasswordResetCode $mail): bool => $mail->hasTo($user->email)
        && preg_match('/^\d{6}$/', $mail->code) === 1);

    $this->get(route('password.otp.verify', ['audience' => 'student']))->assertOk()->assertSee('Kode OTP');
});

test('unknown email receives the same confirmation without receiving an email', function () {
    Mail::fake();

    $this->post(route('password.otp.send', ['audience' => 'student']), ['email' => 'unknown@example.test'])
        ->assertRedirect(route('password.otp.verify', ['audience' => 'student']))
        ->assertSessionHas('status', 'Jika email terdaftar, kode verifikasi akan dikirimkan. Periksa kotak masuk Anda.');

    Mail::assertNothingSent();
});

test('password can be reset with a valid OTP only once', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->post(route('password.otp.send', ['audience' => 'student']), ['email' => $user->email]);

    Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $mail) use ($user): bool {
        $this->post(route('password.otp.reset', ['audience' => 'student']), [
            'code' => $mail->code,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        return true;
    });
});

test('incorrect OTP does not change the password', function () {
    Mail::fake();
    $user = User::factory()->create();
    $originalPassword = $user->password;

    $this->post(route('password.otp.send', ['audience' => 'student']), ['email' => $user->email]);

    $this->post(route('password.otp.reset', ['audience' => 'student']), [
        'code' => '000000',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertSessionHasErrors('code');

    expect($user->fresh()->password)->toBe($originalPassword);
});

test('expired OTP cannot reset the password', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->freezeTime();
    $this->post(route('password.otp.send', ['audience' => 'student']), ['email' => $user->email]);
    $this->travel(11)->minutes();

    Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $mail) use ($user): bool {
        $this->post(route('password.otp.reset', ['audience' => 'student']), [
            'code' => $mail->code,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('code');

        expect(Hash::check('password', $user->fresh()->password))->toBeTrue();

        return true;
    });
});
