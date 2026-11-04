<x-layouts::auth title="Masuk">
    <div class="flex flex-col gap-6">
        <x-auth-header title="Masuk ke MagangHub" description="Gunakan akun yang telah diberikan oleh administrator." />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify
            label="Masuk dengan passkey"
            loading-label="Memverifikasi…"
            separator="Atau lanjutkan dengan email atau NIM/NIDN"
        />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email or academic identifier -->
            <flux:input
                name="email"
                label="Email, NIM, atau NIDN"
                :value="old('email')"
                type="text"
                required
                autofocus
                autocomplete="username"
                placeholder="Email, NIM, atau NIDN"
            />

            <!-- Password -->
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

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        Lupa kata sandi?
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" label="Ingat saya" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    Masuk
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>
