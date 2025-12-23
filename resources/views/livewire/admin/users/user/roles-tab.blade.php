<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Livewire\Volt\Component;

new class extends Component {
    public User $user;
    public $selectedRole = '';

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    // Récupérer uniquement les rôles que l'utilisateur n'a PAS encore
    public function getAvailableRolesProperty()
    {
        return Role::whereNotIn('id', $this->user->roles->pluck('id'))->pluck('name');
    }

    public function assignRole()
    {
        $this->validate(['selectedRole' => 'required|string|exists:roles,name']);

        if (!auth()->user()->can('manage roles')) {
            flash()->error('Non autorisé.');
            return;
        }

        $this->user->assignRole($this->selectedRole);
        $this->reset('selectedRole');
        
        // Rafraîchir
        $this->user->load('roles.permissions');
        $this->dispatch('user-updated');
        
        flash()->success("Rôle ajouté avec succès.");
    }

    public function removeRole($roleName)
    {
        if (!auth()->user()->can('manage roles')) return;

        $this->user->removeRole($roleName);
        $this->user->load('roles.permissions');
        $this->dispatch('user-updated');
        flash()->success("Rôle retiré.");
    }
}; ?>

<div class="space-y-6">
    {{-- Header avec Action d'ajout --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 pb-4">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900">Rôles assignés</h3>
            <p class="text-sm text-zinc-500">Gérez les groupes d'accès de l'utilisateur.</p>
        </div>

        @can('manage roles')
            <div class="flex w-full sm:w-auto gap-2">
                @if($this->availableRoles->count() > 0)
                    <div class="w-full sm:w-48">
                        <flux:select wire:model="selectedRole" placeholder="Choisir un rôle..." >
                            @foreach($this->availableRoles as $role)
                                <flux:select.option value="{{ $role }}">{{ ucfirst($role) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:button variant="primary" wire:click="assignRole" :disabled="empty($selectedRole)">
                        Ajouter
                    </flux:button>
                @else
                    <flux:badge color="zinc">Tous les rôles sont assignés</flux:badge>
                @endif
            </div>
        @endcan
    </div>

    {{-- Liste des rôles --}}
    @if($user->roles->count() > 0)
        <div class="grid grid-cols-1 gap-4">
            @foreach($user->roles as $role)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 border border-zinc-200 rounded-lg bg-white hover:border-primary/30 transition-colors group">
                    
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-primary/10 rounded-lg text-primary">
                                <flux:icon.shield-check variant="micro" />
                            </div>
                            <div>
                                <h4 class="font-medium text-zinc-900">{{ ucfirst($role->name) }}</h4>
                                <div class="text-xs text-zinc-500 mt-0.5 flex items-center gap-2">
                                    <span>ID: {{ $role->id }}</span>
                                    <span>•</span>
                                    <span>{{ $role->permissions->count() }} permission(s) incluse(s)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 sm:mt-0 flex items-center gap-4">
                        {{-- Indicateur visuel des permissions clés (optionnel) --}}
                        <div class="hidden md:flex -space-x-1">
                            @foreach($role->permissions->take(3) as $perm)
                                <div class="size-6 rounded-full bg-zinc-100 border border-white flex items-center justify-center text-[10px] text-zinc-600" title="{{ $perm->name }}">
                                    <flux:icon.key variant="micro" class="size-3" />
                                </div>
                            @endforeach
                            @if($role->permissions->count() > 3)
                                <div class="size-6 rounded-full bg-zinc-100 border border-white flex items-center justify-center text-[9px] font-bold text-zinc-500">
                                    +{{ $role->permissions->count() - 3 }}
                                </div>
                            @endif
                        </div>

                        @can('manage roles')
                            <flux:button 
                                variant="ghost" 
                                size="sm" 
                                class="text-zinc-400 hover:text-red-500"
                                wire:click="removeRole('{{ $role->name }}')"
                                wire:confirm="Retirer le rôle '{{ $role->name }}' ?">
                                <flux:icon.trash class="size-4" />
                            </flux:button>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-center border-2 border-dashed border-zinc-200 rounded-xl bg-zinc-50/50">
            <flux:icon.shield-exclamation class="size-12 text-zinc-300 mb-3" />
            <h3 class="text-zinc-900 font-medium">Aucun rôle</h3>
            <p class="text-zinc-500 text-sm max-w-xs mx-auto">Utilisez le sélecteur ci-dessus pour assigner le premier rôle à cet utilisateur.</p>
        </div>
    @endif
</div>