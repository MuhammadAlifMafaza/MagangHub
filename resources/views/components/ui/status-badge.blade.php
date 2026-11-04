@props([
    'label',
    'tone' => 'neutral',
])

@php
    $classes = [
        'success' => 'border-success/20 bg-success-bg text-success',
        'warning' => 'border-warning/20 bg-warning-bg text-warning',
        'danger' => 'border-danger/20 bg-danger-bg text-danger',
        'info' => 'border-info/20 bg-info-bg text-info',
        'neutral' => 'border-neutral/20 bg-neutral-bg text-neutral',
        'gray' => 'border-neutral/20 bg-neutral-bg text-neutral',
    ];
@endphp

<span {{ $attributes->class(['inline-flex w-fit items-center rounded-full border px-2.5 py-1 text-xs font-semibold', $classes[$tone] ?? $classes['neutral']]) }}>
    {{ $label }}
</span>
