<x-layouts::auth.simple title="Lupa kata sandi">
    @php
        $audience ??= 'student';
        $panel ??= 'admin';
        $loginUrl = $audience === 'staff' ? url("/{$panel}/login") : route('login');
        $accountLabel = $audience === 'staff' ? 'staff' : 'mahasiswa';
    @endphp

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="bg-gradient-to-br from-primary-800 to-primary-700 p-6 text-white sm:p-8">
            <div class="flex items-start gap-4">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white/15" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.75a4.5 4.5 0 0 0-9 0v2.75m-1.25 0h11.5A1.25 1.25 0 0 1 19 11.75v7A1.25 1.25 0 0 1 17.75 20H6.25A1.25 1.25 0 0 1 5 18.75v-7a1.25 1.25 0 0 1 1.25-1.25ZM12 14v2.5" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-white/75">Pemulihan akun {{ $accountLabel }}</p>
                    <h1 class="mt-1 text-2xl font-semibold">Lupa kata sandi?</h1>
                    <p class="mt-2 text-sm leading-6 text-white/80">Masukkan email akun Anda untuk menerima kode verifikasi.</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-6 p-6 sm:p-8">

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.otp.send', ['audience' => $audience]) }}" class="flex flex-col gap-6">
            @csrf
            @if ($audience === 'staff')
                <input type="hidden" name="panel" value="{{ $panel }}">
            @endif
            <flux:input
                name="email"
                label="Alamat email"
                type="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="email"
                placeholder="nama@kampus.ac.id"
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-code-button">
                Kirim kode verifikasi
            </flux:button>
        </form>

        <div class="rounded-xl border border-primary-100 bg-primary-50 p-4 dark:border-primary-900 dark:bg-primary-950/40">
            <p class="text-sm font-semibold text-primary-800 dark:text-primary-100">Setelah kode dikirim</p>
            <ul class="mt-2 list-inside list-disc space-y-1 text-sm leading-5 text-zinc-600 dark:text-zinc-300">
                <li>Kode berlaku selama 10 menit dan hanya dapat digunakan sekali.</li>
                <li>Periksa folder spam jika email belum masuk.</li>
            </ul>
        </div>

        <a href="{{ $loginUrl }}" class="text-center text-sm font-medium text-primary-700 underline underline-offset-4">Kembali ke halaman masuk {{ $accountLabel }}</a>
        </div>
    </div>
</x-layouts::auth.simple>
