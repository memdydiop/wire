<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\User;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $username = '';
    public string $phone = '';
    public string $address = '';
    public string $city = '';
    public string $country = '';

    public function mount()
    {
        $user = Auth::user();
        // Remplissage plus concis
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->username = $user->username ?? '';
        $this->phone = $user->phone ?? '';
        $this->address = $user->address ?? '';
        $this->city = $user->city ?? '';
        $this->country = $user->country ?? '';
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'username' => [
                'nullable',
                'string',
                'min:3',
                'max:50',
                'alpha_dash', // lettres, nombres, tirets, underscores uniquement
                Rule::unique('users')->ignore(Auth::id())
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function save()
    {
        $validated = $this->validate();
        
        // On s'assure que l'email n'est jamais mis à jour ici
        Auth::user()->update($validated);

        flash()->success('Informations mises à jour.');
        
        // Rafraîchir le composant parent (Profile) pour mettre à jour le nom dans le header s'il change
        $this->dispatch('profile-updated'); 
    }

    public function cancel()
    {
        $this->mount();
        $this->resetErrorBag(); // Enlève les messages d'erreur rouges
    }
}; 
?>

<div>
    <div class="mb-6">
        <flux:heading level="2">Informations personnelles</flux:heading>
        <flux:subheading>Mettez à jour vos informations de profil public.</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:input wire:model="name" label="Nom complet" icon="user" required />
            
            <div class="relative opacity-75">
                <flux:input 
                    wire:model="email" 
                    label="Adresse Email" 
                    icon="envelope" 
                    readonly 
                    disabled
                    class="bg-gray-100 cursor-not-allowed text-gray-500"
                />
                <div class="absolute right-3 top-[34px] text-gray-400 pointer-events-none">
                    <flux:icon.lock-closed variant="micro" />
                </div>
            </div>

            <flux:input wire:model.blur="username" label="Nom d'utilisateur" icon="at-symbol" />
            
            <flux:input wire:model="phone" label="Téléphone" icon="phone" />
            
            <flux:input wire:model="city" label="Ville" icon="building-office" />
            
            <flux:input wire:model="country" label="Pays" icon="globe-alt" />

            <div class="md:col-span-2">
                <flux:input wire:model="address" label="Adresse postale" icon="map-pin" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
                <flux:button type="button" wire:click="cancel" variant="ghost">
                    Annuler
                </flux:button>

            <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                Enregistrer
            </flux:button>
        </div>
    </form>
</div>