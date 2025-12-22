@blaze

@props([
    'kbd' => null,
])

@php
$classes = Flux::classes([
    'relative py-1.5 px-2',
    'rounded-md',
    'text-xs text-white',
    'bg-slate-600',
    'p-0 overflow-visible',
]);
@endphp

<div popover="manual" {{ $attributes->class($classes) }} data-flux-tooltip-content>
    {{ $slot }}

    <?php if ($kbd): ?>
        <span class="ps-1 text-zinc-300">{{ $kbd }}</span>
    <?php endif; ?>
</div>
