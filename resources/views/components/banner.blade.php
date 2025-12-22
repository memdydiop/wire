@props([
    'variant' => 'info', // info, success, warning, danger
    'icon' => null,
    'title' => null,
    'dismissible' => false,
    'persistent' => false, // Si true, la bannière ne peut pas être fermée
    'storage_key' => null, // Clé personnalisée pour le localStorage
])

@php
    $variants = [
        'info' => [
            'bg' => 'bg-info/20',
            'border' => 'border-info/50',
            'icon_color' => 'text-info',
            'title_color' => 'text-info',
            'text_color' => 'text-info',
            'default_icon' => 'information-circle',
            'aria_role' => 'status',
        ],
        'success' => [
            'bg' => 'bg-success/20',
            'border' => 'border-success/50',
            'icon_color' => 'text-success',
            'title_color' => 'text-success',
            'text_color' => 'text-success',
            'default_icon' => 'check-circle',
            'aria_role' => 'status',
        ],
        'warning' => [
            'bg' => 'bg-warning/20',
            'border' => 'border-warning/50',
            'icon_color' => 'text-warning',
            'title_color' => 'text-warning',
            'text_color' => 'text-warning',
            'default_icon' => 'exclamation-triangle',
            'aria_role' => 'alert',
        ],
        'danger' => [
            'bg' => 'bg-danger/20',
            'border' => 'border-danger/50',
            'icon_color' => 'text-danger',
            'title_color' => 'text-danger',
            'text_color' => 'text-danger',
            'default_icon' => 'exclamation-circle',
            'aria_role' => 'alert',
        ],
    ];

    $config = $variants[$variant] ?? $variants['info'];
    $iconName = $icon ?? $config['default_icon'];
    
    // Générer une clé unique pour le localStorage
    $dismissKey = $storage_key ?: 'banner-dismissed-' . md5($slot . $title . $variant);
@endphp

<div 
    {{ $attributes->merge(['class' => "{$config['bg']} border {$config['border']} rounded-lg p-2"]) }}
    @if($dismissible && !$persistent) 
        x-data="{ 
            show: true,
            dismissed: localStorage.getItem('{{ $dismissKey }}') === 'true'
        }" 
        x-show="show && !dismissed"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    @endif
    @if($config['aria_role']) 
        role="{{ $config['aria_role'] }}" 
        aria-live="{{ in_array($variant, ['warning', 'danger']) ? 'assertive' : 'polite' }}"
    @endif
>
    <div class="flex items-start gap-3">
        <!-- Icon -->
        @if($iconName)
            <div class="shrink-0 ">
                <x-dynamic-component 
                    :component="'flux::icon.' . $iconName" 
                    class="size-5 {{ $config['icon_color'] }}" 
                />
            </div>
        @endif

        <!-- Content -->
        <div class="flex-1 min-w-0">
            @if($title)
                <p class="text-sm font-semibold {{ $config['title_color'] }} mb-1">
                    {{ $title }}
                </p>
            @endif
            
            <flux:text class="text-sm {{ $config['text_color'] }} {{ $title ? 'mt-1' : '' }}">
                {{ $slot }}
            </flux:text>
        </div>

        <!-- Dismiss button -->
        @if($dismissible && !$persistent)
            <button 
                @click="
                    show = false;
                    setTimeout(() => {
                        localStorage.setItem('{{ $dismissKey }}', 'true');
                    }, 200);
                " 
                type="button"
                class="flex-shrink-0 {{ $config['icon_color'] }} hover:opacity-75 transition-opacity focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-current rounded"
                aria-label="{{ __('Fermer la notification') }}"
            >
                <x-flux::icon.x-mark class="size-5" />
            </button>
        @endif
    </div>
</div>