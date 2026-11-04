@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="MagangHub" {{ $attributes }}>
        <x-slot name="logo" class="flex size-9 items-center justify-center rounded-xl bg-primary-50 p-1">
            <img src="{{ asset('images/brand/Logo.svg') }}" alt="" class="size-full object-contain">
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="MagangHub" {{ $attributes }}>
        <x-slot name="logo" class="flex size-9 items-center justify-center rounded-xl bg-primary-50 p-1">
            <img src="{{ asset('images/brand/Logo.svg') }}" alt="" class="size-full object-contain">
        </x-slot>
    </flux:brand>
@endif
