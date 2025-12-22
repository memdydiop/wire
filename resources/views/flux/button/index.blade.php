@blaze

@php $iconTrailing ??= $attributes->pluck('icon:trailing'); @endphp
@php $iconLeading ??= $attributes->pluck('icon:leading'); @endphp
@php $iconVariant ??= $attributes->pluck('icon:variant'); @endphp

@props([
    'iconTrailing' => null,
    'variant' => 'outline',
    'iconVariant' => null,
    'iconLeading' => null,
    'type' => 'button',
    'loading' => null,
    'align' => 'center',
    'size' => 'base',
    'square' => null,
    'color' => null,
    'inset' => null,
    'icon' => null,
    'kbd' => null,
])

@php
$iconLeading = $icon ??= $iconLeading;

// Button should be a square if it has no text contents...
$square ??= $slot->isEmpty();

// Size-up icons in square/icon-only buttons... (xs buttons just get micro size/style...)
$iconVariant ??= ($size === 'xs')
    ? ($square ? 'micro' : 'micro')
    : ($square ? 'mini' : 'micro');

$iconTrailingVariant ??= $attributes->pluck('icon-trailing:variant', $iconVariant);

// When using the outline icon variant, we need to size it down to match the default icon sizes...
$iconClasses = Flux::classes()
    ->add($iconVariant === 'outline' ? ($square && $size !== 'xs' ? 'size-5' : 'size-4') : '')
    ->add($attributes->pluck('icon:class'))
    ;

$iconTrailingClasses = Flux::classes()
    ->add($iconTrailingVariant === 'outline' ? ($square && $size !== 'xs' ? 'size-5' : 'size-4') : '')
    ->add($attributes->pluck('icon-trailing:class'))
    ;

$isTypeSubmitAndNotDisabledOnRender = $type === 'submit' && ! $attributes->has('disabled');

$isJsMethod = str_starts_with($attributes->whereStartsWith('wire:click')->first() ?? '', '$js.');

$loading ??= $loading ?? ($isTypeSubmitAndNotDisabledOnRender || $attributes->whereStartsWith('wire:click')->isNotEmpty() && ! $isJsMethod);

if ($loading && $type !== 'submit' && ! $isJsMethod) {
    $attributes = $attributes->merge(['wire:loading.attr' => 'data-flux-loading']);

    // We need to add `wire:target` here because without it the loading indicator won't be scoped
    // by method params, causing multiple buttons with the same method but different params to
    // trigger each other's loading indicators...
    if (! $attributes->has('wire:target') && $target = $attributes->whereStartsWith('wire:click')->first()) {
        $attributes = $attributes->merge(['wire:target' => $target], escape: false);
    }
}

$classes = Flux::classes()
    ->add('relative items-center font-normal justify-center gap-2 whitespace-nowrap bg-gradient')
    ->add('disabled:opacity-75 disabled:cursor-default disabled:pointer-events-none')
    ->add(match ($align) {
        'start' => 'justify-start',
        'center' => 'justify-center',
        'end' => 'justify-end',
    })
    ->add(match ($size) { // Size...
        'base' => 'h-9 text-sm rounded-md' . ' ' . (
            $square
                ? 'w-9'
                // If we have an icon, we want to reduce the padding on the side that has the icon...
                : ($iconLeading && $iconLeading !== '' ? 'ps-3' : 'ps-4') . ' ' . ($iconTrailing && $iconTrailing !== '' ? 'pe-3' : 'pe-4')
        ),
        'sm' => 'h-8 text-sm rounded-md' . ' ' . ($square ? 'w-8' : 'px-3'),
        'xs' => 'h-6 text-xs rounded-md' . ' ' . ($square ? 'w-6' : 'px-2'),
    })
    ->add('inline-flex') // Buttons are inline by default but links are blocks, so inline-flex is needed here to ensure link-buttons are displayed the same as buttons...
    ->add($inset ? match ($size) { // Inset...
        'base' => $square
            ? Flux::applyInset($inset, top: '-mt-2.5', right: '-me-2.5', bottom: '-mb-2.5', left: '-ms-2.5')
            : Flux::applyInset($inset, top: '-mt-2.5', right: '-me-4', bottom: '-mb-3', left: '-ms-4'),
        'sm' => $square
            ? Flux::applyInset($inset, top: '-mt-1.5', right: '-me-1.5', bottom: '-mb-1.5', left: '-ms-1.5')
            : Flux::applyInset($inset, top: '-mt-1.5', right: '-me-3', bottom: '-mb-1.5', left: '-ms-3'),
        'xs' => $square
            ? Flux::applyInset($inset, top: '-mt-1', right: '-me-1', bottom: '-mb-1', left: '-ms-1')
            : Flux::applyInset($inset, top: '-mt-1', right: '-me-2', bottom: '-mb-1', left: '-ms-2'),
    } : '')
    ->add(match ($variant) { // Background color...
        'primary' => 'bg-primary/70 hover:bg-primary',
        'secondary' => 'bg-secondary/70 hover:bg-secondary',
        'info' => 'bg-info/70 hover:bg-info',
        'success' => 'bg-success/70 hover:bg-success',
        'warning' => 'bg-warning/70 hover:bg-warning',
        'danger' => 'bg-danger/70 hover:bg-danger',

        'filled' => 'bg-zinc-800/5 hover:bg-zinc-800/10',
        'outline' => 'bg-white hover:bg-zinc-50',
        'danger' => 'bg-red-500 hover:bg-red-600',
        'ghost' => 'bg-transparent hover:bg-zinc-800/5',
        'subtle' => 'bg-transparent hover:bg-zinc-800/5',
    })
    ->add(match ($variant) { // Text color...
        'primary' => 'text-white',
        'secondary' => 'text-[var(--color-accent-foreground)]',
        'info' => 'text-[var(--color-accent-foreground)]',
        'success' => 'text-[var(--color-accent-foreground)]',
        'warning' => 'text-[var(--color-accent-foreground)]',
        'danger' => 'text-[var(--color-accent-foreground)]',
        'filled' => 'text-zinc-800',
        'outline' => 'text-zinc-800',
        'danger' => 'text-white',
        'ghost' => 'text-zinc-800',
        'subtle' => 'text-zinc-500 hover:text-zinc-800',
    })
    ->add(match ($variant) { // Border color...
        'primary' => 'border border-primary/20',
        'secondary' => 'border border-secondary/20',
        'info' => 'border border-info/20',
        'success' => 'border border-success/20',
        'warning' => 'border border-warning/20',
        'danger' => 'border border-danger/20',
        'filled' => 'border border-zinc-200',
        'outline' => 'border border-zinc-200 hover:border-zinc-200 border-b-zinc-300/80',
         default => '',
    })
    // ->add(match ($variant) { // Shadows...
    //     'primary' => 'shadow-[inset_0px_1px_--theme(--color-white/.2)]',
    //     'secondary' => 'shadow-[inset_0px_1px_--theme(--color-white/.2)]',
    //     'primary' => 'shadow-[inset_0px_1px_--theme(--color-white/.2)]',
    //     'primary' => 'shadow-[inset_0px_1px_--theme(--color-white/.2)]',
    //     'primary' => 'shadow-[inset_0px_1px_--theme(--color-white/.2)]',
    //     'primary' => 'shadow-[inset_0px_1px_--theme(--color-white/.2)]',
    //     'danger' => 'shadow-[inset_0px_1px_var(--color-red-500),inset_0px_2px_--theme(--color-white/.15)]',
    //     'outline' => match ($size) {
    //         'base' => 'shadow-xs',
    //         'sm' => 'shadow-xs',
    //         'xs' => 'shadow-none',
    //     },
    //     default => '',
    // })
    ->add(match ($variant) { // Grouped border treatments...
        'ghost' => '',
        'subtle' => '',
        'outline' => '[[data-flux-button-group]_&]:border-s-0 [:is([data-flux-button-group]>&:first-child,_[data-flux-button-group]_:first-child>&)]:border-s-[1px]',
        'filled' => '[[data-flux-button-group]_&]:border-e [:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-0 [[data-flux-button-group]_&]:border-zinc-200/80',
        'danger' => '[[data-flux-button-group]_&]:border-e [:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-0 [[data-flux-button-group]_&]:border-red-600',
        'primary' => '[[data-flux-button-group]_&]:border-e-0 [:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-[1px]',
        'secondary' => '[[data-flux-button-group]_&]:border-e-0 [:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-[1px]',
        'info' => '[[data-flux-button-group]_&]:border-e-0 [:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-[1px]',
        'success' => '[[data-flux-button-group]_&]:border-e-0 [:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-[1px]',
        'warning' => '[[data-flux-button-group]_&]:border-e-0 [:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-[1px]',
    })
    ->add($loading ? [ // Loading states...
        '*:transition-opacity',
        $type === 'submit' ? '[&[disabled]>:not([data-flux-loading-indicator])]:opacity-0' : '[&[data-loading]>:not([data-flux-loading-indicator])]:opacity-0 [&[data-flux-loading]>:not([data-flux-loading-indicator])]:opacity-0',
        $type === 'submit' ? '[&[disabled]>[data-flux-loading-indicator]]:opacity-100' : '[&[data-loading]>[data-flux-loading-indicator]]:opacity-100 [&[data-flux-loading]>[data-flux-loading-indicator]]:opacity-100',
        $type === 'submit' ? '[&[disabled]]:pointer-events-none' : 'data-loading:pointer-events-none data-flux-loading:pointer-events-none',
    ] : [])
    ->add($variant === 'primary' ? match ($color) {
        'primary' => '[--color-accent:var(--color-primary)] [--color-accent-content:var(--color-primary)] [--color-accent-foreground:var(--color-white)]',
        'secondary' => '[--color-accent:var(--color-secondary)] [--color-accent-content:var(--color-secondary)] [--color-accent-foreground:var(--color-white)]',
        'info' => '[--color-accent:var(--color-info)] [--color-accent-content:var(--color-info)] [--color-accent-foreground:var(--color-white)]',
        'success' => '[--color-accent:var(--color-success)] [--color-accent-content:var(--color-success)] [--color-accent-foreground:var(--color-white)]',
        'warning' => '[--color-accent:var(--color-warning)] [--color-accent-content:var(--color-warning)] [--color-accent-foreground:var(--color-white)]',
        'danger' => '[--color-accent:var(--color-danger)] [--color-accent-content:var(--color-danger)] [--color-accent-foreground:var(--color-white)]',
        'slate' => '[--color-accent:var(--color-slate-800)] [--color-accent-content:var(--color-slate-800)] [--color-accent-foreground:var(--color-white)]',
        'gray' => '[--color-accent:var(--color-gray-800)] [--color-accent-content:var(--color-gray-800)] [--color-accent-foreground:var(--color-white)] ',
        'zinc' => '[--color-accent:var(--color-zinc-800)] [--color-accent-content:var(--color-zinc-800)] [--color-accent-foreground:var(--color-white)]',
        'neutral' => '[--color-accent:var(--color-neutral-800)] [--color-accent-content:var(--color-neutral-800)] [--color-accent-foreground:var(--color-white)]',
        'stone' => '[--color-accent:var(--color-stone-800)] [--color-accent-content:var(--color-stone-800)] [--color-accent-foreground:var(--color-white)]',
        'red' => '[--color-accent:var(--color-red-500)] [--color-accent-content:var(--color-red-600)] [--color-accent-foreground:var(--color-white)]',
        'orange' => '[--color-accent:var(--color-orange-500)] [--color-accent-content:var(--color-orange-600)] [--color-accent-foreground:var(--color-white)]',
        'amber' => '[--color-accent:var(--color-amber-400)] [--color-accent-content:var(--color-amber-600)] [--color-accent-foreground:var(--color-amber-950)]',
        'yellow' => '[--color-accent:var(--color-yellow-400)] [--color-accent-content:var(--color-yellow-600)] [--color-accent-foreground:var(--color-yellow-950)]',
        'lime' => '[--color-accent:var(--color-lime-400)] [--color-accent-content:var(--color-lime-600)] [--color-accent-foreground:var(--color-lime-900)]',
        'green' => '[--color-accent:var(--color-green-600)] [--color-accent-content:var(--color-green-600)] [--color-accent-foreground:var(--color-white)]',
        'emerald' => '[--color-accent:var(--color-emerald-600)] [--color-accent-content:var(--color-emerald-600)] [--color-accent-foreground:var(--color-white)]',
        'teal' => '[--color-accent:var(--color-teal-600)] [--color-accent-content:var(--color-teal-600)] [--color-accent-foreground:var(--color-white)]',
        'cyan' => '[--color-accent:var(--color-cyan-600)] [--color-accent-content:var(--color-cyan-600)] [--color-accent-foreground:var(--color-white)]',
        'sky' => '[--color-accent:var(--color-sky-600)] [--color-accent-content:var(--color-sky-600)] [--color-accent-foreground:var(--color-white)] ',
        'blue' => '[--color-accent:var(--color-blue-500)] [--color-accent-content:var(--color-blue-600)] [--color-accent-foreground:var(--color-white)] ',
        'indigo' => '[--color-accent:var(--color-indigo-500)] [--color-accent-content:var(--color-indigo-600)] [--color-accent-foreground:var(--color-white)] ',
        'violet' => '[--color-accent:var(--color-violet-500)] [--color-accent-content:var(--color-violet-600)] [--color-accent-foreground:var(--color-white)] ',
        'purple' => '[--color-accent:var(--color-purple-500)] [--color-accent-content:var(--color-purple-600)] [--color-accent-foreground:var(--color-white)]',
        'fuchsia' => '[--color-accent:var(--color-fuchsia-600)] [--color-accent-content:var(--color-fuchsia-600)] [--color-accent-foreground:var(--color-white)]',
        'pink' => '[--color-accent:var(--color-pink-600)] [--color-accent-content:var(--color-pink-600)] [--color-accent-foreground:var(--color-white)]',
        'rose' => '[--color-accent:var(--color-rose-500)] [--color-accent-content:var(--color-rose-500)] [--color-accent-foreground:var(--color-white)]',
        default => '',
    } : '')
    ;

    // Exempt subtle and ghost buttons from receiving border roundness overrides from button.group...
    $attributes = $attributes->merge([
        'data-flux-group-target' => ! in_array($variant, ['subtle', 'ghost']),
    ]);
@endphp

<flux:with-tooltip :$attributes>
    <flux:button-or-link-pure :$type :attributes="$attributes->class($classes)" data-flux-button>
        <?php if ($loading): ?>
            <div class="absolute inset-0 flex items-center justify-center opacity-0" data-flux-loading-indicator>
                <flux:icon icon="loading" :variant="$iconVariant" :class="$iconClasses" />
            </div>
        <?php endif; ?>

        <?php if (is_string($iconLeading) && $iconLeading !== ''): ?>
            <flux:icon :icon="$iconLeading" :variant="$iconVariant" :class="$iconClasses" />
        <?php elseif ($iconLeading): ?>
            {{ $iconLeading }}
        <?php endif; ?>

        <?php if (($loading || $iconLeading || $iconTrailing) && ! $slot->isEmpty()): ?>
            {{-- If we have a loading indicator, we need to wrap it in a span so it can be a target of *:opacity-0... --}}
            {{-- Also, if we have an icon, we need to wrap it in a span so it can be recognized as a child of the button for :first-child selectors... --}}
            <span>{{ $slot }}</span>
        <?php else: ?>
            {{ $slot }}
        <?php endif; ?>

        <?php if ($kbd): ?>
            <div class="text-xs text-zinc-400 dark:text-zinc-400">{{ $kbd }}</div>
        <?php endif; ?>

        <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
            {{-- Adding the extra margin class inline on the icon component below was causing a double up, so it needs to be added here first... --}}
            <?php $iconClasses->add($square ? '' : '-ms-1'); ?>
            <flux:icon :icon="$iconTrailing" :variant="$iconTrailingVariant" :class="$iconTrailingClasses" />
        <?php elseif ($iconTrailing): ?>
            {{ $iconTrailing }}
        <?php endif; ?>
    </flux:button-or-link-pure>
</flux:with-tooltip>
