<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Attributes\{On, Computed};

new class extends Component {
    
    public ?Role $role = null;
    public bool $showModal = false;
    public array $selectedPermissions = [];
    public string $searchPermission = '';

    /**
     * Ouvrir le modal
     */
    #[On('open-role-permissions')]
    public function openModal($roleId)
    {
        $this->role = Role::with('permissions')->findOrFail($roleId);
        $this->selectedPermissions = $this->role->permissions->pluck('name')->toArray();
        $this->showModal = true;
    }

    /**
     * Fermer le modal
     */
    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['role', 'selectedPermissions', 'searchPermission']);
    }

    /**
     * Sauvegarder les permissions
     */
    public function savePermissions()
    {
        try {
            // Synchroniser les permissions
            $this->role->syncPermissions($this->selectedPermissions);

            flash()->success("Permissions du rôle '{$this->role->name}' mises à jour.");

            // Nettoyer le cache des permissions
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            // Notifier le parent
            $this->dispatch('role-updated');
            $this->closeModal();

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour des permissions', [
                'role_id' => $this->role->id,
                'error' => $e->getMessage()
            ]);

            flash()->error('Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    /**
     * Sélectionner toutes les permissions d'une catégorie
     */
    public function selectCategory($category)
    {
        $categoryPermissions = $this->permissionsByCategory[$category]->pluck('name')->toArray();
        $this->selectedPermissions = array_unique(array_merge($this->selectedPermissions, $categoryPermissions));
    }

    /**
     * Désélectionner toutes les permissions d'une catégorie
     */
    public function deselectCategory($category)
    {
        $categoryPermissions = $this->permissionsByCategory[$category]->pluck('name')->toArray();
        $this->selectedPermissions = array_diff($this->selectedPermissions, $categoryPermissions);
    }

    /**
     * Sélectionner toutes les permissions
     */
    public function selectAll()
    {
        $this->selectedPermissions = Permission::pluck('name')->toArray();
    }

    /**
     * Désélectionner toutes les permissions
     */
    public function deselectAll()
    {
        $this->selectedPermissions = [];
    }

    /**
     * Vérifier si toutes les permissions d'une catégorie sont sélectionnées
     */
    public function isCategoryFullySelected($category): bool
    {
        if (!isset($this->permissionsByCategory[$category])) {
            return false;
        }

        $categoryPermissions = $this->permissionsByCategory[$category]->pluck('name')->toArray();
        return count(array_intersect($this->selectedPermissions, $categoryPermissions)) === count($categoryPermissions);
    }

    /**
     * Liste de toutes les permissions groupées par catégorie
     */
    #[Computed]
    public function permissionsByCategory()
    {
        $permissions = Permission::orderBy('name')->get();

        // Filtrer par recherche
        if ($this->searchPermission) {
            $permissions = $permissions->filter(function($permission) {
                return str_contains(strtolower($permission->name), strtolower($this->searchPermission));
            });
        }
        
        // Grouper par préfixe (view, create, edit, delete, etc.)
        $grouped = $permissions->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            return $parts[0] ?? 'other';
        });

        return $grouped;
    }

    /**
     * Vérifier si le rôle est un rôle système
     */
    #[Computed]
    public function isSystemRole(): bool
    {
        return $this->role && $this->role->name === 'ghost';
    }
}; 

?>

<div>
    <flux:modal wire:model="showModal" class="md:w-[800px]">
        @if($role)
            <form wire:submit="savePermissions" class="space-y-4">
                <div>
                    <flux:heading size="lg">
                        Gérer les permissions : {{ ucfirst($role->name) }}
                    </flux:heading>
                    <flux:subheading>
                        Sélectionnez les permissions à attribuer à ce rôle.
                    </flux:subheading>
                </div>

                @if($this->isSystemRole)
                    <x-banner variant="info" title="Rôle Ghost">
                        Le rôle Ghost possède automatiquement toutes les permissions, actuelles et futures.
                    </x-banner>
                @endif

                <flux:separator />

                <!-- Stats et actions globales -->
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <flux:badge color="blue">
                            {{ count($selectedPermissions) }} / {{ Permission::count() }} sélectionnées
                        </flux:badge>
                    </div>

                    @if(!$this->isSystemRole)
                        <div class="flex items-center gap-2">
                            <flux:button 
                                variant="ghost" 
                                size="sm"
                                wire:click="selectAll"
                                type="button"
                            >
                                Tout sélectionner
                            </flux:button>
                            <flux:button 
                                variant="ghost" 
                                size="sm"
                                wire:click="deselectAll"
                                type="button"
                            >
                                Tout désélectionner
                            </flux:button>
                        </div>
                    @endif
                </div>

                <!-- Recherche -->
                <flux:input 
                    wire:model.live.debounce.300ms="searchPermission"
                    placeholder="Rechercher une permission..."
                    type="search"
                >
                    <x-slot:iconTrailing>
                        <flux:icon.magnifying-glass variant="mini" />
                    </x-slot:iconTrailing>
                </flux:input>

                <!-- Liste des permissions par catégorie -->
                <div class="max-h-[500px] overflow-y-auto border border-gray-200 rounded-lg">
                    <div class="divide-y divide-gray-200">
                        @forelse($this->permissionsByCategory as $category => $permissions)
                            <div class="p-4">
                                <!-- En-tête de catégorie -->
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <flux:heading size="sm" class="font-semibold text-gray-900">
                                            {{ ucfirst($category) }}
                                        </flux:heading>
                                        <flux:badge size="xs" color="zinc">
                                            {{ $permissions->count() }}
                                        </flux:badge>
                                    </div>

                                    @if(!$this->isSystemRole)
                                        <div class="flex items-center gap-1">
                                            @if($this->isCategoryFullySelected($category))
                                                <flux:button 
                                                    variant="ghost" 
                                                    size="xs"
                                                    wire:click="deselectCategory('{{ $category }}')"
                                                    type="button"
                                                >
                                                    Tout désélectionner
                                                </flux:button>
                                            @else
                                                <flux:button 
                                                    variant="ghost" 
                                                    size="xs"
                                                    wire:click="selectCategory('{{ $category }}')"
                                                    type="button"
                                                >
                                                    Tout sélectionner
                                                </flux:button>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <!-- Permissions de la catégorie -->
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($permissions as $permission)
                                        <label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                            <flux:checkbox 
                                                wire:model.live="selectedPermissions" 
                                                value="{{ $permission->name }}"
                                                :disabled="$this->isSystemRole"
                                            />
                                            <span class="text-sm text-gray-700">
                                                {{ $permission->name }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500">
                                <flux:icon.magnifying-glass class="size-12 text-gray-300 mx-auto mb-2" />
                                <flux:text>Aucune permission trouvée.</flux:text>
                            </div>
                        @endforelse
                    </div>
                </div>

                <flux:separator />

                <!-- Actions -->
                <div class="flex justify-between items-center gap-2">
                    <flux:button 
                        variant="ghost" 
                        wire:click="closeModal"
                        type="button"
                    >
                        Annuler
                    </flux:button>

                    @if(!$this->isSystemRole)
                        <flux:button 
                            type="submit" 
                            variant="primary" 
                            icon="check"
                        >
                            Enregistrer les permissions
                        </flux:button>
                    @else
                        <flux:button 
                            variant="ghost" 
                            wire:click="closeModal"
                            type="button"
                        >
                            Fermer
                        </flux:button>
                    @endif
                </div>
            </form>
        @endif
    </flux:modal>
</div>