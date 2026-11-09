<x-layouts::auth.simple title="Lupa kata sandi">
    <div class="flex flex-col gap-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 sm:p-8">
        <div class="flex flex-col gap-2 text-center">
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">Lupa kata sandi?</h1>
            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">Masukkan email akun Anda. Kami akan mengirim kode verifikasi jika email tersebut terdaftar.</p>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.otp.send') }}" class="flex flex-col gap-6">
            @csrf
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

        <div class="flex flex-col gap-3 text-center text-sm text-zinc-500 dark:text-zinc-400">
            <a href="{{ route('login') }}" class="font-medium text-primary-700 underline underline-offset-4">Kembali ke portal mahasiswa</a>
            <a href="{{ url('/admin/login') }}" class="underline underline-offset-4">Masuk sebagai staff</a>
        </div>
    </div>
</x-layouts::auth.simple>
