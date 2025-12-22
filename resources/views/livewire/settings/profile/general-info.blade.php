<?php
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component {
    public string $name;
    public string $username;
    public string $phone;
    public string $address;
    public string $city;
    public string $country;

    public function mount()
    {
        
        $user = Auth::user();
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->username = $user->username ?? '';
        $this->address = $user->address ?? '';
        $this->phone = $user->phone ?? '';
        $this->city = $user->city ?? '';
        $this->country = $user->country ?? '';
        $this->avatar_url = $user->avatar_url ?? '';
    }

    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[\pL\s\-\']+$/u' // Lettres, espaces, tirets et apostrophes
            ],
            'username' => [
                'nullable',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_-]+$/', // Lettres, chiffres, underscore et tiret
                Rule::unique('users')->ignore(Auth::id())
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^[\d\s\+\-\(\)]+$/', // Chiffres, espaces, +, -, (, )
                'min:10',
                'max:20',
                Rule::unique('users')->ignore(Auth::id())
            ],
            'address' => [
                'nullable',
                'string',
                'max:255'
            ],
            'city' => [
                'nullable',
                'string',
                'min:2',
                'max:100',
            ],
            'country' => [
                'nullable',
                'string',
                'min:2',
                'max:100',
            ],
        ];
    }

    public function messages()
    {
        return [
            // Name
            'name.required' => 'Le nom est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins 2 caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'name.regex' => 'Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes.',
            
            // Username
            'username.min' => 'Le nom d\'utilisateur doit contenir au moins 3 caractères.',
            'username.max' => 'Le nom d\'utilisateur ne peut pas dépasser 50 caractères.',
            'username.regex' => 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, tirets et underscores.',
            'username.unique' => 'Ce nom d\'utilisateur est déjà utilisé.',
            
            
            // Phone
            'phone.regex' => 'Le numéro de téléphone n\'est pas valide. Utilisez uniquement des chiffres, espaces et les caractères + - ( ).',
            'phone.min' => 'Le numéro de téléphone doit contenir au moins 8 caractères.',
            'phone.max' => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            
            // Address
            'address.max' => 'L\'adresse ne peut pas dépasser 255 caractères.',
            
            // City
            'city.min' => 'Le nom de la ville doit contenir au moins 2 caractères.',
            'city.max' => 'Le nom de la ville ne peut pas dépasser 100 caractères.',
            
            // Country
            'country.min' => 'Le nom du pays doit contenir au moins 2 caractères.',
            'country.max' => 'Le nom du pays ne peut pas dépasser 100 caractères.',
        ];
    }

    public function validationAttributes()
    {
        return [
            'name' => 'nom',
            'username' => 'nom d\'utilisateur',
            'phone' => 'téléphone',
            'address' => 'adresse',
            'city' => 'ville',
            'country' => 'pays',
        ];
    }

    public function save()
    {
        $user = Auth::user();

        $validated = $this->validate();

        $user->fill($validated);

        try {
            
            // Log des changements
            $changes = array_diff_assoc($validated, $user->only(array_keys($validated)));
            
            $user->update($validated);

            flash()->success('Vos informations ont été mises à jour avec succès.');
            $this->dispatch('profile-updated');
        } catch (\Exception $e) {
            flash()->error('Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        $this->mount();
        flash()->info('Modifications annulées.');
    }

    // Validation en temps réel pour username
    public function updatedUsername($value)
    {
        if ($value) {
            $this->validateOnly('username');
        }
    }

    // Validation en temps réel pour phone
    public function updatedPhone($value)
    {
        if ($value) {
            $this->validateOnly('phone');
        }
    }
}; 
?>

<div>
    <div class="mb-4">
        <flux:subheading class="text-sm text-gray-600">
            Gérez vos informations personnelles
        </flux:subheading>
    </div>

    <form wire:submit="save">
        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <!-- Nom complet -->
                <flux:input 
                    wire:model.blur="name" 
                    label="Nom complet" 
                    placeholder="Jean Dupont" 
                    required 
                />
                
                <!-- Nom d'utilisateur -->
                <flux:input 
                    wire:model.blur="username" 
                    label="Nom d'utilisateur" 
                    placeholder="jean_dupont" 
                />
                
                <!-- Téléphone -->
                <flux:input 
                    wire:model.blur="phone" 
                    label="Téléphone" 
                    type="tel" 
                    placeholder="00 00 00 00 00" 
                />
                
                <!-- Ville -->
                <flux:input 
                    wire:model.blur="city" 
                    label="Ville" 
                    placeholder="Abidjan" 
                />
                
                <!-- Pays -->
                <flux:input 
                    wire:model.blur="country" 
                    label="Pays" 
                    placeholder="Côte d'Ivoire" 
                />

                <!-- Adresse complète -->
                <flux:input 
                    wire:model.blur="address" 
                    label="Adresse" 
                    placeholder="123 Rue de la République" 
                />
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-end gap-2">
                <flux:button 
                    type="button" 
                    wire:click="cancel" 
                    variant="filled"
                    wire:loading.attr="disabled">
                    Annuler
                </flux:button>
                <flux:button 
                    type="submit" 
                    variant="primary" 
                    icon="check"
                    wire:loading.attr="disabled"
                    wire:target="save">
                    Enregistrer
                </flux:button>
            </div>
        </div>
    </form>
</div>