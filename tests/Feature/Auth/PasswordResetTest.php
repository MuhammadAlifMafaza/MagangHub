<?php

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
    $this->get(route('password.request'))->assertOk()->assertSee('Kirim kode verifikasi');
});

test('password reset code is mailed to the requested account', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->post(route('password.otp.send'), ['email' => $user->email])
        ->assertRedirect(route('password.otp.verify'));

    Mail::assertSent(PasswordResetCode::class, fn (PasswordResetCode $mail): bool => $mail->hasTo($user->email)
        && preg_match('/^\d{6}$/', $mail->code) === 1);

    $this->get(route('password.otp.verify'))->assertOk()->assertSee('Kode OTP');
});

test('unknown email receives the same confirmation without receiving an email', function () {
    Mail::fake();

    $this->post(route('password.otp.send'), ['email' => 'unknown@example.test'])
        ->assertRedirect(route('password.otp.verify'))
        ->assertSessionHas('status', 'Jika email terdaftar, kode verifikasi akan dikirimkan. Periksa kotak masuk Anda.');

    Mail::assertNothingSent();
});

test('password can be reset with a valid OTP only once', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->post(route('password.otp.send'), ['email' => $user->email]);

    Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $mail) use ($user): bool {
        $this->post(route('password.otp.reset'), [
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

    $this->post(route('password.otp.send'), ['email' => $user->email]);

    $this->post(route('password.otp.reset'), [
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
    $this->post(route('password.otp.send'), ['email' => $user->email]);
    $this->travel(11)->minutes();

    Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $mail) use ($user): bool {
        $this->post(route('password.otp.reset'), [
            'code' => $mail->code,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('code');

        expect(Hash::check('password', $user->fresh()->password))->toBeTrue();

        return true;
    });
});
