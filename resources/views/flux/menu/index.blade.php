@blaze

@php
$classes = Flux::classes()
    ->add('[:where(&)]:min-w-8 p-[.3125rem]')
    ->add('rounded-lg shadow-xs')
    ->add('border border-zinc-200')
    ->add('bg-white')
    ->add('focus:outline-hidden')
    ;
@endphp

<ui-menu
    {{ $attributes->class($classes) }}
    popover="manual"
    data-flux-menu
>
    {{ $slot }}
</ui-menu>
