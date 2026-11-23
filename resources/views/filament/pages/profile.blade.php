<x-filament-panels::page>
    <div class="mx-auto w-full max-w-6xl">
        <div class="grid gap-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-10">
            @include('filament.components.account-page-navigation', [
                'active' => 'profile',
                'profileUrl' => route('filament.admin.pages.profile'),
                'settingsUrl' => route('filament.admin.pages.settings'),
            ])

            <div class="min-w-0 max-w-3xl">
                <div class="mb-5 flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 sm:flex-row sm:items-center dark:border-white/10 dark:bg-white/5">
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-primary-50 text-lg font-semibold text-primary-700 dark:bg-primary-400/10 dark:text-primary-300">
                        {{ auth()->user()->initials() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-base font-semibold text-gray-950 dark:text-white">{{ auth()->user()->name }}</h2>
                        <p class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-filament::badge color="success">Akun aktif</x-filament::badge>
                        <x-filament::badge color="gray">Administrator</x-filament::badge>
                        @if (auth()->user()->hasVerifiedEmail())
                            <x-filament::badge color="success">Email terverifikasi</x-filament::badge>
                        @else
                            <x-filament::badge color="warning">Email belum terverifikasi</x-filament::badge>
                        @endif
                    </div>
                </div>

                <x-filament::section>
                    <x-slot name="heading">Profil administrator</x-slot>
                    <x-slot name="description">Kelola identitas akun administrator MagangHub.</x-slot>

                    <form wire:submit="save" class="space-y-6">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <label class="space-y-2">
                                <span class="text-sm font-medium">Nama lengkap</span>
                                <input wire:model="name" type="text" autocomplete="name" required class="fi-input-wrp-input w-full rounded-lg border-gray-300 shadow-sm transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5" />
                                @error('name') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium">Alamat email</span>
                                <input wire:model="email" type="email" autocomplete="email" required class="fi-input-wrp-input w-full rounded-lg border-gray-300 shadow-sm transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5" />
                                @error('email') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <div class="rounded-lg bg-gray-50 p-4 text-sm dark:bg-white/5">
                            <span class="font-medium">Peran</span>
                            <span class="ms-2 text-gray-600 dark:text-gray-300">Administrator</span>
                        </div>

                        <div class="flex justify-end border-t border-gray-200 pt-4 dark:border-white/10">
                            <x-filament::button type="submit" icon="heroicon-m-check">Simpan profil</x-filament::button>
                        </div>
                    </form>
                </x-filament::section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
