<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-bg text-ink">
        <a href="#main-content" class="sr-only z-50 rounded-lg bg-surface px-4 py-3 text-primary-700 focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">Lewati ke konten utama</a>
        <flux:sidebar sticky collapsible="mobile" class="min-h-dvh border-e border-line bg-surface">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate class="!text-ink" />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav aria-label="Navigasi utama">
                <div class="grid gap-1">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">Menu utama</p>
                    <a href="{{ route('dashboard') }}" @class(['flex min-h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700', 'bg-primary-50 text-primary-700' => request()->routeIs('dashboard'), 'text-ink hover:bg-primary-50 hover:text-primary-700' => ! request()->routeIs('dashboard')]) @if (request()->routeIs('dashboard')) aria-current="page" @endif wire:navigate>
                        <flux:icon.home variant="outline" class="size-5 shrink-0" />
                        <span>Dashboard</span>
                    </a>
                    @if (auth()->user()->role === \App\Enums\UserRole::Student)
                        <a href="{{ route('guidance.student') }}" @class(['flex min-h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700', 'bg-primary-50 text-primary-700' => request()->routeIs('guidance.*'), 'text-ink hover:bg-primary-50 hover:text-primary-700' => ! request()->routeIs('guidance.*')]) @if (request()->routeIs('guidance.*')) aria-current="page" @endif wire:navigate>
                            <flux:icon.calendar-days variant="outline" class="size-5 shrink-0" />
                            <span>Jadwal Pembimbingan</span>
                        </a>
                        <a href="{{ route('mahasiswa.laporan.index') }}" @class(['flex min-h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700', 'bg-primary-50 text-primary-700' => request()->routeIs('mahasiswa.laporan.*') || request()->routeIs('laporan.show'), 'text-ink hover:bg-primary-50 hover:text-primary-700' => ! (request()->routeIs('mahasiswa.laporan.*') || request()->routeIs('laporan.show'))]) @if (request()->routeIs('mahasiswa.laporan.*') || request()->routeIs('laporan.show')) aria-current="page" @endif wire:navigate>
                            <flux:icon.document-text variant="outline" class="size-5 shrink-0" />
                            <span>Laporan Mingguan</span>
                        </a>
                    @endif
                </div>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav aria-label="Pengaturan akun">
                <a href="{{ route('profile.edit') }}" @class(['flex min-h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700', 'bg-primary-50 text-primary-700' => request()->routeIs('profile.*'), 'text-ink hover:bg-primary-50 hover:text-primary-700' => ! request()->routeIs('profile.*')]) @if (request()->routeIs('profile.*')) aria-current="page" @endif wire:navigate>
                    <flux:icon.user-circle variant="outline" class="size-5 shrink-0" />
                    <span>Profil</span>
                </a>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            Profil
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            Keluar
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <div id="main-content" tabindex="-1" class="min-h-svh">{{ $slot }}</div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
