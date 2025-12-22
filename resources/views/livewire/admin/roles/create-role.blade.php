<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Attributes\{Validate, Computed};

new class extends Component {
    
    #[Validate('required|string|max:50|unique:roles,name|alpha_dash')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $description = '';

    public array $selectedPermissions = [];

    /**
     * Messages de validation
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du rôle est requis.',
            'name.unique' => 'Ce nom de rôle existe déjà.',
            'name.alpha_dash' => 'Le nom ne peut contenir que des lettres, chiffres, tirets et underscores.',
            'name.max' => 'Le nom ne peut pas dépasser 50 caractères.',
            'description.max' => 'La description ne peut pas dépasser 255 caractères.',
        ];
    }

    /**
     * Réinitialiser le formulaire
     */
    public function resetForm()
    {
        $this->reset(['name', 'description', 'selectedPermissions']);
        $this->resetValidation();
    }

    /**
     * Créer le rôle
     */
    public function create()
    {
        $this->validate();

        try {
            // Créer le rôle
            $role = Role::create([
                'name' => strtolower($this->name),
                'description' => $this->description ?: null,
                'guard_name' => 'web',
            ]);

            // Assigner les permissions sélectionnées
            if (!empty($this->selectedPermissions)) {
                $role->syncPermissions($this->selectedPermissions);
            }

            flash()->success("Rôle '{$role->name}' créé avec succès.");

            // Notifier le parent
            $this->dispatch('role-updated');
            $this->dispatch('close-modal', 'create-role');
            $this->resetForm();

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du rôle', [
                'name' => $this->name,
                'error' => $e->getMessage()
            ]);

            flash()->error('Erreur lors de la création du rôle : ' . $e->getMessage());
        }
    }

    /**
     * Liste de toutes les permissions groupées par catégorie
     */
    #[Computed]
    public function permissionsByCategory()
    {
        $permissions = Permission::orderBy('name')->get();
        
        // Grouper par préfixe (view, create, edit, delete, etc.)
        $grouped = $permissions->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            return $parts[0] ?? 'other';
        });

        return $grouped;
    }
}; 

?>

<div>
    <flux:modal name="create-role" class="md:w-[600px]">
        <form wire:submit="create" class="space-y-4">
            <div>
                <flux:heading size="lg">Créer un nouveau rôle</flux:heading>
                <flux:subheading>
                    Définissez le nom, la description et les permissions du rôle.
                </flux:subheading>
            </div>

            <flux:separator />

            <div class="space-y-4">
                <!-- Nom du rôle -->
                <flux:input 
                    wire:model.blur="name" 
                    label="Nom du rôle" 
                    type="text" 
                    required 
                    autofocus 
                    placeholder="manager"
                >
                    <x-slot:description>
                        Lettres minuscules, chiffres, tirets et underscores uniquement
                    </x-slot:description>
                </flux:input>

                <!-- Description -->
                <flux:textarea 
                    wire:model.blur="description" 
                    label="Description (optionnel)"
                    rows="3"
                    placeholder="Décrivez les responsabilités de ce rôle..."
                />

                <!-- Permissions -->
                <div class="space-y-4">
                    <flux:legend>Permissions</flux:legend>
                    <flux:description>
                        Sélectionnez les permissions à attribuer à ce rôle
                    </flux:description>

                    <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg p-4 space-y-4">
                        @foreach($this->permissionsByCategory as $category => $permissions)
                            <div>
                                <flux:subheading class="mb-2">
                                    {{ ucfirst($category) }}
                                </flux:subheading>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($permissions as $permission)
                                        <label class="flex items-center gap-2 p-0.5 hover:bg-gray-50 rounded cursor-pointer">
                                            <flux:checkbox 
                                                wire:model="selectedPermissions" 
                                                value="{{ $permission->name }}"
                                            />
                                            <span class="text-sm text-gray-700">
                                                {{ $permission->name }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <flux:separator />

            <!-- Actions -->
            <div class="flex justify-between items-center gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" wire:click="resetForm">
                        Annuler
                    </flux:button>
                </flux:modal.close>

                <flux:button 
                    type="submit" 
                    variant="primary" 
                    icon="shield-check"
                >
                    Créer le rôle
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>