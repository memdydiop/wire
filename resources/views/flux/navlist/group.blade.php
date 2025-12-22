@blaze

@props([
    'expandable' => false,
    'expanded' => true,
    'heading' => null,
    'variant' => null,
    'accent' => true,
    'iconVariant' => 'outline',
    'icon' => null,
])

@php
// Button should be a square if it has no text contents...
$square ??= $slot->isEmpty();

// Size-up icons in square/icon-only buttons...
$iconClasses = Flux::classes($square ? 'size-5!' : 'size-4!');

$btnClasses = Flux::classes()
    ->add('w-full relative h-9 flex items-center gap-3 group/disclosure-button')
    ->add($square ? 'px-2.5!' : '')
    ->add('py-0 text-xs! text-start w-full px-3 my-1')
    ->add('text-navlist-item bg-zinc-800/5 border border-zinc-800/10 rounded-lg')
    ->add(match ($variant) {
        default => match ($accent) {
            true => [
                'data-current:text-navlist-item-active hover:data-current:text-navlist-item-active',
                'data-current:bg-zinc-800/15',
                'hover:text-navlist-item-active hover:bg-zinc-800/10',
                
            ],
            false => [
                'data-current:text-zinc-800',
                'data-current:bg-zinc-800/[4%]',
                'hover:text-zinc-800 hover:bg-zinc-800/[4%]',
            ],
        },
    })
    ;
@endphp


<?php if ($expandable && $heading): ?>
    <ui-disclosure {{ $attributes->class('group/disclosure overflow-hidden' ) }} @if ($expanded === true) open @endif data-flux-navlist-group>
        <button type="button" {{ $attributes->class($btnClasses) }}>

            <?php if ($icon): ?>
                <div class="relative">
                    <?php if (is_string($icon) && $icon !== ''): ?>
                        <flux:icon :$icon :variant="$iconVariant" class="{!! $iconClasses !!}" />
                    <?php else: ?>
                        {{ $icon }}
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <span class="text-sm leading-none mr-auto">{{ $heading }}</span>

            <div class="p-0">
                <flux:icon.chevron-right id="disclosure-chevron" class="size-3! group-data-open/disclosure-button:rotate-90" />
            </div>
        </button>

        <div class="relative hidden data-open:block overflow-hidden" @if ($expanded === true) data-open @endif>

            {{ $slot }}
            
        </div>
    </ui-disclosure>
<?php elseif ($heading): ?>
    <div {{ $attributes->class('block space-y-[2px]') }}>
        <div class="px-3 py-2">
            <div class="text-xs text-zinc-400 leading-none">{{ $heading }}</div>
        </div>

        <div>
            {{ $slot }}
        </div>
    </div>
<?php else: ?>
    <div {{ $attributes->class('block space-y-[2px]') }}>
        {{ $slot }}
    </div>
<?php endif; ?>
