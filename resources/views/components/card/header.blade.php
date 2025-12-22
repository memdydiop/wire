@props([
    'actions' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between px-3 py-2 border-b border-gray-100']) }}>
    <div class="flex flex-col">
        {{-- Le titre est rendu via le slot principal pour x-card.title --}}
        <flux:heading :level="3" size="md">
            @if(isset($title))
                {{ $title }}
            @else
                {{ $slot }}
            @endif
        </flux:heading>
        
        {{-- Le composant x-card.description est maintenant géré par le fichier créé ci-dessus --}}
        @isset($description)
            <flux:text size="xs">{{ $description }}</flux:text>
        @endif
    </div>

    @isset($actions)
        <div class="flex items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>