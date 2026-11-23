<x-filament-panels::page>
    <div class="mx-auto w-full max-w-6xl">
        <div class="grid gap-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-10">
            @include('filament.components.account-page-navigation', [
                'active' => 'settings',
                'profileUrl' => route('filament.admin.pages.profile'),
                'settingsUrl' => route('filament.admin.pages.settings'),
            ])

            <div class="min-w-0 max-w-4xl space-y-6">
                <x-filament::section>
                    <x-slot name="heading">Keamanan akun</x-slot>
                    <x-slot name="description">Perbarui kata sandi secara berkala dan jangan gunakan kata sandi yang sama di layanan lain.</x-slot>

                    <form wire:submit="updatePassword" class="space-y-5">
                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="space-y-2 md:col-span-2">
                                <span class="text-sm font-medium">Kata sandi saat ini</span>
                                <input wire:model="current_password" type="password" autocomplete="current-password" required class="fi-input-wrp-input w-full rounded-lg border-gray-300 shadow-sm transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5" />
                                @error('current_password') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium">Kata sandi baru</span>
                                <input wire:model="password" type="password" autocomplete="new-password" required class="fi-input-wrp-input w-full rounded-lg border-gray-300 shadow-sm transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5" />
                                @error('password') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium">Konfirmasi kata sandi baru</span>
                                <input wire:model="password_confirmation" type="password" autocomplete="new-password" required class="fi-input-wrp-input w-full rounded-lg border-gray-300 shadow-sm transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5" />
                            </label>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
                            <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">Pastikan kata sandi baru memenuhi kebijakan keamanan akun.</p>
                            <x-filament::button type="submit" icon="heroicon-m-key" wire:loading.attr="disabled" wire:target="updatePassword">
                                Perbarui kata sandi
                            </x-filament::button>
                        </div>
                    </form>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">Pintasan administrasi</x-slot>
                    <x-slot name="description">Kelola data dan pantau aktivitas utama MagangHub.</x-slot>

                    <div class="grid gap-4 md:grid-cols-2">
                        <a href="{{ $accountsUrl }}" class="group rounded-xl border border-gray-200 p-5 transition hover:border-primary-500 hover:bg-primary-50/50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:border-white/10 dark:hover:bg-white/5">
                            <div class="flex items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-info-50 text-info-700 dark:bg-info-400/10 dark:text-info-300">
                                    <x-filament::icon icon="heroicon-o-users" class="size-5" aria-hidden="true" />
                                </span>
                                <div>
                                    <h2 class="font-semibold text-gray-950 dark:text-white">Akun pengguna</h2>
                                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Atur data akun mahasiswa, dosen, dan status aksesnya.</p>
                                </div>
                            </div>
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-primary-700 group-hover:underline dark:text-primary-300">Kelola akun <x-filament::icon icon="heroicon-m-arrow-right" class="size-4" aria-hidden="true" /></span>
                        </a>
                        <a href="{{ $recapUrl }}" class="group rounded-xl border border-gray-200 p-5 transition hover:border-primary-500 hover:bg-primary-50/50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:border-white/10 dark:hover:bg-white/5">
                            <div class="flex items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-300">
                                    <x-filament::icon icon="heroicon-o-chart-bar" class="size-5" aria-hidden="true" />
                                </span>
                                <div>
                                    <h2 class="font-semibold text-gray-950 dark:text-white">Rekap administrasi</h2>
                                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Tinjau periode magang, laporan, dan aktivitas administrasi.</p>
                                </div>
                            </div>
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-primary-700 group-hover:underline dark:text-primary-300">Buka rekap <x-filament::icon icon="heroicon-m-arrow-right" class="size-4" aria-hidden="true" /></span>
                        </a>
                        <a href="{{ $dashboardUrl }}" class="group rounded-xl border border-gray-200 p-5 transition hover:border-primary-500 hover:bg-primary-50/50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:border-white/10 dark:hover:bg-white/5">
                            <div class="flex items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-300">
                                    <x-filament::icon icon="heroicon-o-squares-2x2" class="size-5" aria-hidden="true" />
                                </span>
                                <div>
                                    <h2 class="font-semibold text-gray-950 dark:text-white">Ringkasan sistem</h2>
                                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Lihat ringkasan aktivitas dan indikator operasional aplikasi.</p>
                                </div>
                            </div>
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-primary-700 group-hover:underline dark:text-primary-300">Buka dashboard <x-filament::icon icon="heroicon-m-arrow-right" class="size-4" aria-hidden="true" /></span>
                        </a>
                    </div>
                </x-filament::section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
