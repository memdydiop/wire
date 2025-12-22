<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Livewire\Attributes\{On, Validate, Computed};
use Illuminate\Validation\Rule;

new class extends Component {
    
    public ?Role $role = null;
    public bool $showModal = false;

    #[Validate]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $description = '';

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('roles', 'name')->ignore($this->role?->id),
            ],
            'description' => 'nullable|string|max:255',
        ];
    }

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
     * Ouvrir le modal avec un rôle
     */
    #[On('open-edit-role')]
    public function openModal($roleId)
    {
        $this->role = Role::findOrFail($roleId);
        $this->name = $this->role->name;
        $this->description = $this->role->description ?? '';
        $this->showModal = true;
    }

    /**
     * Fermer le modal
     */
    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['role', 'name', 'description']);
        $this->resetValidation();
    }

    /**
     * Mettre à jour le rôle
     */
    public function update()
    {
        // Protection des rôles système
        if (in_array($this->role->name, ['ghost', 'admin'])) {
            flash()->error('Les rôles système ne peuvent pas être modifiés.');
            return;
        }

        $this->validate();

        try {
            $this->role->update([
                'name' => strtolower($this->name),
                'description' => $this->description ?: null,
            ]);

            flash()->success("Rôle '{$this->role->name}' mis à jour avec succès.");

            // Nettoyer le cache des permissions
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            // Notifier le parent
            $this->dispatch('role-updated');
            $this->closeModal();

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du rôle', [
                'role_id' => $this->role->id,
                'error' => $e->getMessage()
            ]);

            flash()->error('Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    /**
     * Vérifier si le rôle est un rôle système
     */
    #[Computed]
    public function isSystemRole(): bool
    {
        return $this->role && in_array($this->role->name, ['ghost', 'admin']);
    }
}; 

?>

<div>
    <flux:modal wire:model="showModal" class="md:w-96">
        @if($role)
            <form wire:submit="update" class="space-y-4">
                <div>
                    <flux:heading size="lg">Modifier le rôle</flux:heading>
                    <flux:subheading>
                        Modifiez le nom et la description du rôle.
                    </flux:subheading>
                </div>

                @if($this->isSystemRole)
                    <x-banner variant="warning" title="Rôle système protégé">
                        Ce rôle est essentiel au fonctionnement du système et ne peut pas être modifié.
                    </x-banner>
                @endif

                <flux:separator />

                <div class="space-y-6">
                    <!-- Nom du rôle -->
                    <flux:input 
                        wire:model.blur="name" 
                        label="Nom du rôle" 
                        type="text" 
                        required 
                        :disabled="$this->isSystemRole"
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
                        :disabled="$this->isSystemRole"
                        placeholder="Décrivez les responsabilités de ce rôle..."
                    />

                    <!-- Info sur les permissions -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start gap-2">
                            <flux:icon.information-circle class="size-5 text-blue-600 flex-shrink-0 mt-0.5" />
                            <div>
                                <p class="text-sm text-blue-900">
                                    Pour gérer les permissions de ce rôle, utilisez l'option 
                                    <strong>"Gérer les permissions"</strong> dans le menu.
                                </p>
                            </div>
                        </div>
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
                            Mettre à jour
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