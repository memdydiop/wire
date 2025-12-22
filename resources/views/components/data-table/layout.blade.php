@props([
    'heading' => 'Liste',
    'subheading' => '',
    'perPageOptions' => [10, 15, 25, 50, 100],
])

<div class="w-full">

    <div class="flex items-center justify-between mb-4">
        <flux:heading size="sm">{{ $heading }}</flux:heading>
        @isset($actions)
            <div class="flex items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
    <div class="mb-4 flex flex-wrap justify-between items-center gap-2">

        <div class="flex flex-wrap items-center gap-2">

            <flux:select size="sm" wire:model.live="perPage" class="w-20! " placeholder="Lignes">
                @foreach ($perPageOptions as $option)
                    <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input size="sm" class="w-48!" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Rechercher..." />

            @if (isset($filters))
                <div class="flex items-center gap-2">
                    <flux:dropdown>
                        <flux:button square size="sm">
                            <flux:icon.funnel class="w-4 h-4" />
                        </flux:button>

                        <flux:menu variant="primary">
                            {{ $filters }}
                        </flux:menu>

                    </flux:dropdown>
                </div>
            @endif

        </div>

        @if (isset($bulkActions) && count($this->selected) > 0)
            <div class="relative flex items-center gap-2 animate-in fade-in slide-in-from-right-4 duration-300">
                {{ $bulkActions }}
                <flux:button size="sm" icon="x-mark" wire:click="clearSelection" />
            </div>
        @endif

    </div>

    <div wire:loading.delay.longest class="absolute inset-0 z-50 bg-white/50 flex items-center justify-center">
        <flux:icon.loading class="animate-spin text-gray-400 w-8 h-8" />
    </div>

    <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="pl-3 pr-0 py-2 text-left w-px">
                        <flux:checkbox wire:model.live="selectAll" />
                    </th>
                    {{ $headers }}
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                {{ $rows }}
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $slot }}
    </div>
</div>
