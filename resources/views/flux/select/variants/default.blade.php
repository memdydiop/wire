@blaze

@props([
    'name' => $attributes->whereStartsWith('wire:model')->first(),
    'placeholder' => null,
    'invalid' => null,
    'size' => null,
])

@php
$invalid ??= ($name && $errors->has($name));

$classes = Flux::classes()
    ->add('appearance-none leading-none rounded-md') // Strip the browser's default <select> styles...
    ->add('w-full ps-3 pe-10 block')
    ->add(match ($size) {
        default => 'h-9 py-1.5 text-base',
        'sm' => 'h-8 py-2 text-sm',
        'xs' => 'h-6 text-xs',
    })
    ->add('shadow-xs border')
    ->add('bg-white ')
    ->add('text-zinc-700 disabled:text-zinc-500')
    // Make the placeholder match the text color of standard input placeholders...
    ->add('has-[option.placeholder:checked]:text-zinc-400')
    // Options on Windows don't inherit dark mode styles, so we need to force them...
    ->add('disabled:shadow-none')
    ->add($invalid
        ? 'border border-red-500'
        : 'border border-zinc-200 border-b-zinc-300/80'
    )
    ;
@endphp

<select
    {{ $attributes->class($classes) }}
    @if ($invalid) aria-invalid="true" data-invalid @endif
    @isset ($name) name="{{ $name }}" @endisset
    @if (is_numeric($size)) size="{{ $size }}" @endif
    data-flux-control
    data-flux-select-native
    data-flux-group-target
>
    <?php if ($placeholder): ?>
        <option value="" disabled selected class="placeholder">{{ $placeholder }}</option>
    <?php endif; ?>

    {{ $slot }}
</select>
