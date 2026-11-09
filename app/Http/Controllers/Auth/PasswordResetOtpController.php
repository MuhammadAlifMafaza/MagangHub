<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
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
    public function create(Request $request, string $audience): View|RedirectResponse
    {
        if (! $request->session()->has('password_otp_email') || $request->session()->get('password_otp_audience') !== $audience) {
            return redirect()->route('password.request', [
                'audience' => $request->session()->get('password_otp_audience', 'student'),
                'panel' => $request->session()->get('password_otp_panel'),
            ]);
        }

        return view('pages::auth.verify-password-otp');
    }

    public function store(Request $request, string $audience): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'panel' => ['nullable', Rule::in(['admin', 'dosen'])],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $panel = $audience === 'staff' ? ($validated['panel'] ?? 'admin') : null;
        $request->session()->put('password_otp_email', $email);
        $request->session()->put('password_otp_audience', $audience);
        $request->session()->put('password_otp_panel', $panel);
        $requestKey = 'password-otp-request:'.$audience.':'.hash('sha256', $email.'|'.$request->ip());
        $ipKey = 'password-otp-request-ip:'.$audience.':'.hash('sha256', $request->ip());
        $status = 'Jika email terdaftar, kode verifikasi akan dikirimkan. Periksa kotak masuk Anda.';

        if (RateLimiter::tooManyAttempts($requestKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            return redirect()->route('password.otp.verify', ['audience' => $audience])->with('status', $status);
        }

        RateLimiter::hit($requestKey, 3600);
        RateLimiter::hit($ipKey, 3600);

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        $email = $user?->email ?? $email;
        $request->session()->put('password_otp_email', $email);

        Password::broker(config('fortify.passwords'))->sendResetLink(
            ['email' => $email],
            function (User $user) use ($audience): string {
                if (! $this->canResetPasswordForAudience($user, $audience)) {
                    Password::broker(config('fortify.passwords'))->deleteToken($user);

                    return Password::RESET_LINK_SENT;
                }

                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

                DB::table(config('auth.passwords.users.table'))->updateOrInsert(
                    ['email' => $user->email],
                    ['token' => Hash::make($code), 'created_at' => now()],
                );

                Mail::to($user->email)->send(new PasswordResetCode($code));

                return Password::RESET_LINK_SENT;
            },
        );

        return redirect()->route('password.otp.verify', ['audience' => $audience])->with('status', $status);
    }

    public function reset(Request $request, string $audience): RedirectResponse
    {
        $email = $request->session()->get('password_otp_email');

        if (! is_string($email) || $request->session()->get('password_otp_audience') !== $audience) {
            return redirect()->route('password.request', [
                'audience' => $request->session()->get('password_otp_audience', 'student'),
                'panel' => $request->session()->get('password_otp_panel'),
            ]);
        }

        $key = 'password-otp-verify:'.$audience.':'.hash('sha256', $email.'|'.$request->ip());

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

        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! $this->canResetPasswordForAudience($user, $audience)) {
            return back()->withErrors(['code' => 'Kode tidak valid atau sudah kedaluwarsa.']);
        }

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
        $panel = $request->session()->get('password_otp_panel', 'admin');
        $request->session()->forget('password_otp_email');
        $request->session()->forget(['password_otp_audience', 'password_otp_panel']);

        $loginUrl = $audience === 'staff' && in_array($panel, ['admin', 'dosen'], true)
            ? url("/{$panel}/login")
            : route('login');

        return redirect($loginUrl)->with('status', 'Kata sandi berhasil diperbarui. Silakan masuk kembali.');
    }

    private function canResetPasswordForAudience(User $user, string $audience): bool
    {
        return match ($audience) {
            'student' => $user->role === UserRole::Student,
            'staff' => in_array($user->role, [UserRole::Admin, UserRole::Lecturer], true),
            default => false,
        };
    }
}
