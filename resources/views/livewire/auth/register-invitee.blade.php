<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    // Champs Formulaire
    public string $name = '';
    public string $email = ''; 
    public string $password = '';
    public string $password_confirmation = '';
    public ?string $username = null;
    
    // Nouveau système de pays/téléphone
    public string $country = 'CI'; // Par défaut : Côte d'Ivoire
    public ?string $phone = null;

    // Logique interne
    public ?string $invitationToken = null;
    public ?Invitation $invitation = null;
    public ?string $errorMessage = null;

    // Liste des pays disponibles (Drapeau + Indicatif)
    public function getCountriesList(): array
    {
        return [
            ['code' => 'CI', 'name' => 'Côte d\'Ivoire', 'dial' => '+225', 'flag' => '🇨🇮'],
            ['code' => 'FR', 'name' => 'France', 'dial' => '+33', 'flag' => '🇫🇷'],
            ['code' => 'SN', 'name' => 'Sénégal', 'dial' => '+221', 'flag' => '🇸🇳'],
            ['code' => 'BF', 'name' => 'Burkina Faso', 'dial' => '+226', 'flag' => '🇧🇫'],
            ['code' => 'ML', 'name' => 'Mali', 'dial' => '+223', 'flag' => '🇲🇱'],
            ['code' => 'CM', 'name' => 'Cameroun', 'dial' => '+237', 'flag' => '🇨🇲'],
            ['code' => 'BE', 'name' => 'Belgique', 'dial' => '+32', 'flag' => '🇧🇪'],
            ['code' => 'CA', 'name' => 'Canada', 'dial' => '+1', 'flag' => '🇨🇦'],
        ];
    }

    public function mount(): void
    {
        $this->invitationToken = request()->route('token');

        if (!$this->invitationToken) {
            $this->errorMessage = "Lien d'invitation manquant.";
            return;
        }

        $this->loadInvitation();
    }

    protected function loadInvitation(): void
    {
        $this->invitation = Invitation::where('token', $this->invitationToken)->first();

        // Vérifications défensives
        if (!$this->invitation) {
            $this->errorMessage = "Invitation introuvable.";
            return;
        }

        if (User::where('email', $this->invitation->email)->exists()) {
            session()->flash('info', 'Compte déjà existant. Veuillez vous connecter.');
            $this->redirect(route('login'), navigate: true);
            return;
        }

        if (!$this->invitation->isValid()) {
            $this->errorMessage = "Ce lien d'invitation a expiré ou a déjà été utilisé.";
            return;
        }

        // Pré-remplissage
        $this->email = $this->invitation->email;
        
        // Génération intelligente du nom/username si vide
        if (empty($this->name)) {
            $namePart = explode('@', $this->email)[0];
            $cleanName = preg_replace('/[0-9]+/', '', $namePart); 
            $this->name = ucwords(str_replace(['.', '_', '-'], ' ', $cleanName));
            
            // Suggestion de pseudo (lettres uniquement + suffixe aléatoire)
            $this->username = strtolower(preg_replace('/[^a-zA-Z]/', '', $cleanName)) . rand(10, 99);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'username' => ['nullable', 'string', 'max:50', 'min:3', 'unique:users,username', 'alpha_dash'],
            'country' => ['required', 'string', 'size:2'],
            
            // Validation Dynamique via Laravel-Phone
            'phone' => [
                'nullable', 
                'string', 
                "phone:{$this->country}", // Valide le format selon le pays choisi (ex: 10 chiffres pour CI)
                'unique:users,phone'
            ],
        ];
    }

    public function register(): void
    {
        if ($this->errorMessage) return;

        // Normalisation AVANT la validation unique
        if ($this->phone) {
            $this->phone = User::normalizePhone($this->phone, $this->country);
        }

        $this->validate();

        // Verrouillage (Lock) pour éviter double soumission
        $freshInvitation = Invitation::where('token', $this->invitationToken)
            ->lockForUpdate()
            ->first();

        if (!$freshInvitation || !$freshInvitation->isValid()) {
            $this->errorMessage = "L'invitation a expiré pendant votre saisie.";
            return;
        }

        try {
            // Appel au modèle User pour la logique métier
            $user = User::createFromInvitation([
                'name' => $this->name,
                'password' => $this->password,
                'username' => $this->username,
                'phone' => $this->phone,
                'country' => $this->country,
            ], $freshInvitation);

            Auth::login($user, true);

            Log::info('Nouvel utilisateur via invitation', ['id' => $user->id, 'email' => $user->email]);
            flash()->success('Bienvenue ! Votre compte a été créé.');
            $this->redirect(route('dashboard'), navigate: true);

        } catch (\Exception $e) {
            Log::error('Erreur inscription invitation', ['error' => $e->getMessage()]);
            $this->addError('base', 'Une erreur est survenue lors de la création du compte.');
        }
    }
};
?>

<div>
    @if ($errorMessage)
        <div class="flex flex-col items-center justify-center text-center space-y-4 py-12">
            <div class="bg-red-50 p-4 rounded-full">
                <flux:icon.x-mark class="w-8 h-8 text-red-600" />
            </div>
            <flux:heading size="lg" class="text-red-800">Lien invalide</flux:heading>
            <p class="text-gray-600 max-w-sm">{{ $errorMessage }}</p>
            <flux:button href="{{ route('login') }}" variant="subtle">Retour à la connexion</flux:button>
        </div>
    @else
        <div class="mb-8 text-center">
            <flux:heading size="xl">Finaliser l'inscription</flux:heading>
            <flux:subheading class="mt-2">
                Email : <span class="font-medium text-gray-900">{{ $email }}</span>
            </flux:subheading>
        </div>

        @error('base')
            <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-md text-sm border border-red-200">
                {{ $message }}
            </div>
        @enderror

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <form wire:submit="register" class="space-y-5">

                <flux:input wire:model="name" label="Nom complet *" placeholder="Votre nom" required autofocus />

                <div class="grid gap-5 sm:grid-cols-1">
                    <flux:input wire:model="username" label="Nom d'utilisateur" placeholder="Pseudo public" />
                    
                    <div class="space-y-2">
                        <flux:label>Numéro de téléphone</flux:label>
                        <div class="flex gap-2">
                            <div class="w-[140px] flex-shrink-0">
                                <flux:select wire:model.live="country" class="w-full">
                                    @foreach($this->getCountriesList() as $c)
                                        <flux:select.option value="{{ $c['code'] }}">
                                            <span class="flex items-center gap-2 truncate">
                                                <span>{{ $c['flag'] }}</span>
                                                <span class="text-gray-500 text-xs">{{ $c['dial'] }}</span>
                                            </span>
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div class="flex-1">
                                <flux:input 
                                    wire:model="phone" 
                                    type="tel" 
                                    placeholder="Ex: 07 08 09 10..." 
                                />
                            </div>
                        </div>
                        @error('phone') 
                            <p class="text-xs text-red-500 font-medium">{{ $message }}</p> 
                        @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:input wire:model="password" label="Mot de passe *" type="password" required />
                    <flux:input wire:model="password_confirmation" label="Confirmer *" type="password" required />
                </div>

                <div class="pt-2">
                    <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                        <span wire:loading.remove>Créer mon compte</span>
                        <span wire:loading>Traitement en cours...</span>
                    </flux:button>
                </div>
            </form>
        </div>
        
        <div class="mt-6 text-center text-xs text-gray-500">
            En vous inscrivant, vous acceptez nos <a href="#" class="underline">conditions d'utilisation</a>.
        </div>
    @endif
</div>