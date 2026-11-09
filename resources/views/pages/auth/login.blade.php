<x-layouts::auth.simple title="Masuk Mahasiswa" :wide="true">
    <main class="grid w-full overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900 lg:grid-cols-[minmax(0,1.1fr)_minmax(24rem,0.9fr)]">
        <aside class="student-login-brand relative hidden min-w-0 flex-col justify-between overflow-hidden p-10 text-white lg:flex xl:p-14">
            <div aria-hidden="true" class="absolute -end-24 -top-24 size-80 rounded-full border-[40px] border-white/10"></div>
            <div class="relative">
                <img src="{{ asset('images/brand/MagangHub-Logo-tag.svg') }}" alt="MagangHub" class="h-12 w-auto rounded-lg bg-white px-3 py-2">
                <p class="mt-16 text-sm font-semibold uppercase tracking-[0.18em] text-white/70">Portal mahasiswa</p>
                <h1 class="mt-4 max-w-xl text-4xl font-semibold leading-tight xl:text-5xl">Mulai perjalanan magangmu di sini.</h1>
                <p class="mt-5 max-w-lg text-base leading-7 text-white/75">Pantau laporan mingguan, jadwal bimbingan, dan perkembangan kegiatan magang dalam satu ruang.</p>
            </div>
            <p class="relative text-sm text-white/60">Institut Widya Pratama Pekalongan</p>
        </aside>

        <section class="min-w-0 p-6 sm:p-10 xl:p-14">
            <div class="mb-8 lg:hidden">
                <img src="{{ asset('images/brand/MagangHub-Logo-tag.svg') }}" alt="MagangHub" class="h-10 w-auto">
            </div>
            <p class="text-sm font-semibold text-primary-700">Selamat datang kembali</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">Masuk ke MagangHub</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Gunakan akun mahasiswa yang telah diberikan oleh administrator.</p>

            <div class="mt-8 flex flex-col gap-6">
                <x-auth-session-status class="text-center" :status="session('status')" />
                <x-passkey-verify
                    label="Masuk dengan passkey"
                    loading-label="Memverifikasi…"
                    separator="Atau masuk dengan email atau NIM"
                />

                <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
                    @csrf
                    <flux:input
                        name="email"
                        label="Email atau NIM"
                        :value="old('email')"
                        type="text"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Masukkan email atau NIM"
                    />

                    <div class="relative">
                        <flux:input
                            name="password"
                            label="Kata sandi"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi"
                            viewable
                        />
                        <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                            Lupa kata sandi?
                        </flux:link>
                    </div>

                    <flux:checkbox name="remember" label="Ingat saya" :checked="old('remember')" />
                    <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                        Masuk sebagai mahasiswa
                    </flux:button>
                </form>

            </div>
        </section>
    </main>
</x-layouts::auth.simple>
