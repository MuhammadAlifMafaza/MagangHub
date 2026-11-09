<x-layouts::auth title="Verifikasi kode OTP">
    @php
        $audience = session('password_otp_audience', 'student');
        $panel = session('password_otp_panel', 'admin');
        $loginUrl = $audience === 'staff' && in_array($panel, ['admin', 'dosen'], true)
            ? url("/{$panel}/login")
            : route('login');
        $accountLabel = $audience === 'staff' ? 'staff' : 'mahasiswa';
    @endphp

    <div class="flex flex-col gap-6">
        <x-auth-header title="Verifikasi kode" description="Masukkan kode 6 digit yang kami kirim ke email akun {{ $accountLabel }}, lalu buat kata sandi baru." />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.otp.reset') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="code"
                label="Kode OTP"
                type="text"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                autocomplete="one-time-code"
                required
                autofocus
                placeholder="000000"
            />

            <flux:input
                name="password"
                label="Kata sandi baru"
                type="password"
                autocomplete="new-password"
                required
                viewable
            />

            <flux:input
                name="password_confirmation"
                label="Konfirmasi kata sandi baru"
                type="password"
                autocomplete="new-password"
                required
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full">
                Verifikasi dan ubah kata sandi
            </flux:button>
        </form>

        <form method="POST" action="{{ route('password.otp.send') }}" class="text-center">
            @csrf
            <input type="hidden" name="email" value="{{ session('password_otp_email') }}">
            <input type="hidden" name="audience" value="{{ $audience }}">
            @if ($audience === 'staff')
                <input type="hidden" name="panel" value="{{ $panel }}">
            @endif
            <button type="submit" class="text-sm font-medium text-primary-700 underline underline-offset-4">
                Kirim ulang kode
            </button>
        </form>

        <div class="text-center text-sm text-zinc-500 dark:text-zinc-400">
            <a href="{{ $loginUrl }}" class="underline underline-offset-4">Kembali ke halaman masuk {{ $accountLabel }}</a>
        </div>
    </div>
</x-layouts::auth>
