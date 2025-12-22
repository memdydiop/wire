@blaze

@props([
    'size' => 'default',
])

@php
$classes = Flux::classes()
    ->add(match ($size) {
        'xl' => 'text-xl',
        'lg' => 'text-lg',
        'base' => 'text-base',
        default => 'text-sm',
        'xs' => 'text-xs',
    })
    ->add('[:where(&)]:text-zinc-500')
    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-subheading>
    {{ $slot }}
</div>
