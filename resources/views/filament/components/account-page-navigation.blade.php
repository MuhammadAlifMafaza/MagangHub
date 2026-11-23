@props([
    'active',
    'profileUrl',
    'settingsUrl',
])

<nav aria-label="Navigasi profil dan pengaturan" class="flex gap-1 overflow-x-auto rounded-xl border border-gray-200 bg-white p-1 dark:border-white/10 dark:bg-white/5 lg:block lg:space-y-1 lg:border-0 lg:bg-transparent lg:p-0 dark:lg:bg-transparent">
    <a
        href="{{ $profileUrl }}"
        @if ($active === 'profile') aria-current="page" @endif
        @class([
            'min-w-36 flex-1 rounded-lg px-3 py-2.5 text-sm transition-colors lg:block lg:flex-none',
            'bg-gray-100 text-gray-950 dark:bg-white/10 dark:text-white' => $active === 'profile',
            'text-gray-600 hover:bg-gray-50 hover:text-gray-950 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => $active !== 'profile',
        ])
    >
        <span class="block font-medium">Profil</span>
        <span class="mt-0.5 hidden text-xs text-gray-500 dark:text-gray-400 lg:block">Identitas dan informasi akun</span>
    </a>

    <a
        href="{{ $settingsUrl }}"
        @if ($active === 'settings') aria-current="page" @endif
        @class([
            'min-w-36 flex-1 rounded-lg px-3 py-2.5 text-sm transition-colors lg:block lg:flex-none',
            'bg-gray-100 text-gray-950 dark:bg-white/10 dark:text-white' => $active === 'settings',
            'text-gray-600 hover:bg-gray-50 hover:text-gray-950 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => $active !== 'settings',
        ])
    >
        <span class="block font-medium">Pengaturan</span>
        <span class="mt-0.5 hidden text-xs text-gray-500 dark:text-gray-400 lg:block">Keamanan dan preferensi</span>
    </a>
</nav>
