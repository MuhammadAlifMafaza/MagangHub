@php($userName = filament()->getUserName(filament()->auth()->user()))

@if (filled($userName))
    <span
        class="max-w-24 truncate text-xs font-medium text-gray-700 sm:max-w-40 sm:text-sm dark:text-gray-200"
        title="{{ $userName }}"
        aria-label="Pengguna saat ini: {{ $userName }}"
    >
        {{ $userName }}
    </span>
@endif
