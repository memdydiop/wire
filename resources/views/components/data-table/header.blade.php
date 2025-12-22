@props(['column' => null, 'sortable' => true, 'label' => ''])

<th 
    @if($sortable && $column) wire:click="sortByColumn('{{ $column }}')" @endif
    {{ $attributes->class([
        'px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider',
        'cursor-pointer hover:bg-gray-100 select-none' => ($sortable && $column)
    ]) }}
>
    <div class="flex items-center gap-2">
        <span>{{ $label ?: $slot }}</span>
        
        @if($sortable && $column)
            <span class="w-4 flex flex-col items-center justify-center">
                {{-- Logic to show Up/Down arrows based on current Livewire state --}}
                @if ($this->sortBy === $column)
                    @if ($this->sortDirection === 'asc')
                        <flux:icon.chevron-up class="w-3 h-3 text-gray-800" />
                    @else
                        <flux:icon.chevron-down class="w-3 h-3 text-gray-800" />
                    @endif
                @else
                    <flux:icon.chevron-up-down class="w-4 h-4 text-gray-300" />
                @endif
            </span>
        @endif
    </div>
</th>