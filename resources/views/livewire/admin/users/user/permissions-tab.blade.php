<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Livewire\Volt\Component;

new class extends Component {
    public User $user;
    public $search = '';
    public $permissionToAdd = '';

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    // Liste filtrée des permissions que l'utilisateur n'a PAS encore (ni directes, ni via rôles)
    public function getAvailablePermissionsProperty()
    {
        return Permission::where('name', 'like', '%' . $this->search . '%')
            ->whereNotIn('id', $this->user->getAllPermissions()->pluck('id'))
            ->limit(10) // Limite pour la perf UI
            ->get();
    }

    public function addPermission($permissionName)
    {
        if (!auth()->user()->can('manage permissions')) return;

        $this->user->givePermissionTo($permissionName);
        $this->dispatch('user-updated');
        $this->permissionToAdd = ''; // Reset select if used
        flash()->success("Permission '{$permissionName}' accordée.");
    }

    public function revokePermission($permissionName)
    {
        if (!auth()->user()->can('manage permissions')) return;

        $this->user->revokePermissionTo($permissionName);
        $this->dispatch('user-updated');
        flash()->success("Permission révoquée.");
    }
}; ?>

<div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- Colonne Gauche : Liste des permissions directes --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-zinc-900">Permissions directes</h3>
                    <p class="text-xs text-zinc-500">Exceptions accordées hors des rôles.</p>
                </div>
                <flux:badge color="zinc">{{ $user->getDirectPermissions()->count() }}</flux:badge>
            </div>

            @if($user->getDirectPermissions()->count() > 0)
                <div class="bg-white border border-zinc-200 rounded-lg divide-y divide-zinc-100 overflow-hidden">
                    @foreach($user->getDirectPermissions() as $permission)
                        <div class="flex items-center justify-between p-3 hover:bg-zinc-50">
                            <div class="flex items-center gap-3">
                                <flux:icon.key variant="micro" class="text-zinc-400" />
                                <span class="text-sm font-medium text-zinc-700">{{ $permission->name }}</span>
                            </div>
                            @can('manage permissions')
                                <flux:button size="xs" variant="ghost" class="text-zinc-400 hover:text-red-500" 
                                    wire:click="revokePermission('{{ $permission->name }}')">
                                    <flux:icon.x-mark class="size-4" />
                                </flux:button>
                            @endcan
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-6 bg-zinc-50 rounded-lg border border-dashed border-zinc-200 text-center">
                    <p class="text-sm text-zinc-500 italic">Aucune permission directe. L'utilisateur hérite tout de ses rôles.</p>
                </div>
            @endif
        </div>

        {{-- Colonne Droite : Outil d'ajout rapide --}}
        @can('manage permissions')
        <div class="bg-zinc-50 p-4 rounded-lg border border-zinc-200 h-fit">
            <h4 class="font-medium text-sm text-zinc-900 mb-3">Accorder une exception</h4>
            
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Rechercher une permission..." icon="magnifying-glass" class="mb-3" />

            <div class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                @forelse($this->availablePermissions as $perm)
                    <button wire:click="addPermission('{{ $perm->name }}')" 
                        class="w-full flex items-center justify-between p-2 text-left text-xs bg-white border border-zinc-200 rounded hover:border-primary hover:ring-1 hover:ring-primary transition-all group">
                        <span class="truncate pr-2">{{ $perm->name }}</span>
                        <flux:icon.plus class="size-3 text-zinc-300 group-hover:text-primary" />
                    </button>
                @empty
                    @if(strlen($search) > 0)
                        <p class="text-xs text-zinc-400 text-center py-2">Aucun résultat trouvé.</p>
                    @else
                        <p class="text-xs text-zinc-400 text-center py-2">Tapez pour rechercher...</p>
                    @endif
                @endforelse
            </div>
        </div>
        @endcan
    </div>
</div>