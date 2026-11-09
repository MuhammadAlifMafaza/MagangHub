<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Fortify\Actions\CompletePasswordReset;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class PasswordResetOtpController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (! session()->has('password_otp_email')) {
            return redirect()->route('password.request');
        }

        return view('pages::auth.verify-password-otp');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'audience' => ['nullable', Rule::in(['student', 'staff'])],
            'panel' => ['nullable', Rule::in(['admin', 'dosen'])],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $audience = $validated['audience'] ?? 'student';
        $panel = $audience === 'staff' ? ($validated['panel'] ?? 'admin') : null;
        $request->session()->put('password_otp_email', $email);
        $request->session()->put('password_otp_audience', $audience);
        $request->session()->put('password_otp_panel', $panel);
        $requestKey = 'password-otp-request:'.hash('sha256', $email.'|'.$request->ip());
        $ipKey = 'password-otp-request-ip:'.hash('sha256', $request->ip());
        $status = 'Jika email terdaftar, kode verifikasi akan dikirimkan. Periksa kotak masuk Anda.';

        if (RateLimiter::tooManyAttempts($requestKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            return redirect()->route('password.otp.verify')->with('status', $status);
        }

        RateLimiter::hit($requestKey, 3600);
        RateLimiter::hit($ipKey, 3600);

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        $email = $user?->email ?? $email;
        $request->session()->put('password_otp_email', $email);

        Password::broker(config('fortify.passwords'))->sendResetLink(
            ['email' => $email],
            function (User $user): string {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

                DB::table(config('auth.passwords.users.table'))->updateOrInsert(
                    ['email' => $user->email],
                    ['token' => Hash::make($code), 'created_at' => now()],
                );

                Mail::to($user->email)->send(new PasswordResetCode($code));

                return Password::RESET_LINK_SENT;
            },
        );

        return redirect()->route('password.otp.verify')->with('status', $status);
    }

    public function reset(Request $request): RedirectResponse
    {
        $email = $request->session()->get('password_otp_email');

        if (! is_string($email)) {
            return redirect()->route('password.request');
        }

        $key = 'password-otp-verify:'.hash('sha256', $email.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'code' => 'Terlalu banyak percobaan. Minta kode baru untuk melanjutkan.',
            ]);
        }

        $request->merge(['email' => $email]);
        RateLimiter::hit($key, 600);

        $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', PasswordRule::default(), 'confirmed'],
        ]);

        $status = Password::broker(config('fortify.passwords'))->reset(
            [
                'email' => $email,
                'token' => $request->string('code')->toString(),
                'password' => $request->string('password')->toString(),
                'password_confirmation' => $request->string('password_confirmation')->toString(),
            ],
            function (User $user, string $password): void {
                app(ResetsUserPasswords::class)->reset($user, [
                    'password' => $password,
                    'password_confirmation' => $password,
                ]);

                app(CompletePasswordReset::class)(Auth::guard(config('fortify.guard')), $user);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['code' => 'Kode tidak valid atau sudah kedaluwarsa.']);
        }

        RateLimiter::clear($key);
        $audience = $request->session()->get('password_otp_audience', 'student');
        $panel = $request->session()->get('password_otp_panel', 'admin');
        $request->session()->forget('password_otp_email');
        $request->session()->forget(['password_otp_audience', 'password_otp_panel']);

        $loginUrl = $audience === 'staff' && in_array($panel, ['admin', 'dosen'], true)
            ? url("/{$panel}/login")
            : route('login');

        return redirect($loginUrl)->with('status', 'Kata sandi berhasil diperbarui. Silakan masuk kembali.');
    }
}
