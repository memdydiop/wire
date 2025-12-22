<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Log;

new #[Layout('components.layouts.auth')] class extends Component {
    // Propriétés du formulaire
    public string $name = '';
    public string $email = ''; 
    public string $password = '';
    public string $password_confirmation = '';
    public ?string $username = null;
    public ?string $phone = null;

    // Invitation
    public ?string $invitationToken = null;
    public ?Invitation $invitation = null;

    /**
     * Initialisation du composant
     */
    public function mount(): void
    {
        // Récupération sécurisée du token
        $this->invitationToken = request()->route('token');

        if (!$this->invitationToken) {
            abort(403, 'Token d\'invitation requis');
        }

        $this->loadAndValidateInvitation();
    }

    /**
     * Charge et valide l'invitation en amont
     */
    protected function loadAndValidateInvitation(): void
    {
        // 1. Récupérer l'invitation AVANT d'incrémenter les tentatives
        $this->invitation = Invitation::where('token', $this->invitationToken)->first();

        if (!$this->invitation) {
            abort(404, 'Invitation non trouvée');
        }

        // 2. Incrémenter le compteur de tentatives avec l'ID de l'invitation
        $attemptsKey = "invitation_attempts:{$this->invitation->id}";
        $attempts = Cache::get($attemptsKey, 0);
        Cache::put($attemptsKey, $attempts + 1, now()->addHour());

        // 3. Vérifier si un compte existe déjà pour cet email
        if (User::where('email', $this->invitation->email)->exists()) {
            session()->flash('info', 'Un compte existe déjà pour cet email. Veuillez vous connecter.');
            $this->redirect(route('login'), navigate: true);
            return;
        }

        // 4. Vérifier la validité temporelle et l'état
        if (!$this->invitation->isValid()) {
            abort(403, $this->invitation->isExpired() 
                ? 'Cette invitation a expiré.' 
                : 'Cette invitation a déjà été utilisée.');
        }

        // 5. Vérifier si le token est compromis (trop de tentatives)
        if ($this->invitation->tooManyAttempts()) {
            abort(403, 'Trop de tentatives sur cette invitation. Veuillez contacter l\'administrateur.');
        }

        // 6. Pré-remplir le nom si disponible (format email)
        if (empty($this->name)) {
            $emailParts = explode('@', $this->invitation->email);
            $this->name = ucfirst(str_replace(['.', '_'], ' ', $emailParts[0]));
        }

        // Remplir l'email pour l'affichage
        $this->email = $this->invitation->email;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'username' => ['nullable', 'string', 'max:50', 'min:3', 'unique:users,username', 'alpha_dash', 'regex:/^[a-zA-Z0-9_\-]+$/'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone', 'regex:/^[\+\d\s\-\(\)]{10,20}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom complet est requis.',
            'name.min' => 'Le nom doit contenir au moins 2 caractères.',
            'password.required' => 'Le mot de passe est requis.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'username.unique' => 'Ce nom d\'utilisateur est déjà utilisé.',
            'username.alpha_dash' => 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, tirets et underscores.',
            'username.regex' => 'Le nom d\'utilisateur contient des caractères invalides.',
            'username.min' => 'Le nom d\'utilisateur doit contenir au moins 3 caractères.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'phone.regex' => 'Le format du numéro de téléphone est invalide.',
        ];
    }

    /**
     * Enregistrer l'utilisateur
     */
    public function register(): void
    {
        $this->validate();

        // Sécurité finale : Re-vérifier l'invitation avec verrouillage
        $freshInvitation = Invitation::where('token', $this->invitationToken)
            ->lockForUpdate()
            ->first();

        if (!$freshInvitation || !$freshInvitation->isValid()) {
            session()->flash('error', $freshInvitation?->isExpired() 
                ? 'L\'invitation a expiré.' 
                : 'L\'invitation n\'est plus valide.');
            return;
        }

        try {
            DB::transaction(function () use ($freshInvitation) {
                
                // 1. Créer l'utilisateur
                $user = User::create([
                    'name' => trim($this->name),
                    'email' => strtolower(trim($freshInvitation->email)),
                    'password' => Hash::make($this->password),
                    'username' => $this->username ? strtolower(trim($this->username)) : null,
                    'phone' => $this->phone ? preg_replace('/[^0-9+]/', '', $this->phone) : null,
                    'email_verified_at' => now(),
                    'last_login_at' => now(),
                    'last_login_ip' => request()->ip(),
                ]);

                // 2. Assigner le rôle
                $user->assignRole($freshInvitation->role);
                // 3. Marquer l'invitation comme acceptée
                $freshInvitation->markAsAccepted();

                // 4. Événement et Authentification
                event(new Registered($user));
                Auth::login($user, true); // "Remember me"

                // 5. Log de succès
                Log::info('Nouvel utilisateur inscrit via invitation', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'role' => $freshInvitation->role,
                    'invitation_id' => $freshInvitation->id,
                ]);
            });

            // Redirection avec message de succès
            flash()->success('Votre compte a été créé avec succès !');
            $this->redirect(route('dashboard'), navigate: true);

        } catch (\Exception $e) {
            Log::error('Erreur inscription invitation', [
                'email' => $this->email,
                'invitation_token' => $this->invitationToken,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            flash()->error('Une erreur technique est survenue lors de la création de votre compte. Veuillez réessayer.');
            
        }
    }
};
?>

<div>
    <div class="mb-6 flex w-full flex-col text-center">
        <flux:heading size="xl" class="mb-0!">Créer votre compte</flux:heading>
        <flux:subheading>
            Vous rejoignez : <strong>{{ config('app.name') }}</strong>
        </flux:subheading>
    </div>

    <x-auth-session-status class="text-center" :status="session('status')" />

   

    @if ($invitation)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-start">
                <flux:icon.envelope class="h-5 w-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" />
                <div class="flex-1 min-w-0">
                    <flux:text class="text-sm font-medium text-blue-800">
                        Inscription pour : <strong class="break-all">{{ $invitation->email }}</strong>
                    </flux:text>
                    <flux:text class="text-sm text-blue-800">
                        Rôle : <span class="font-semibold">{{ ucfirst($invitation->role) }}</span>
                    </flux:text>
                    <flux:text class="text-xs text-blue-600 mt-2">
                        <flux:icon.clock class="inline-block h-3 w-3 mr-1" />
                        {{ $invitation->timeUntilExpiry() }}
                    </flux:text>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-200">
        <form wire:submit="register" class="space-y-6" wire:loading.class="opacity-50">

            <flux:input 
                wire:model="name" 
                label="Nom complet *" 
                type="text" 
                required 
                autofocus 
                placeholder="Nom et Prénoms"
            />

            <flux:input 
                wire:model="email" 
                label="Adresse Email" 
                type="email" 
                disabled 
                class="bg-gray-100 cursor-not-allowed"
            />

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:input 
                    wire:model="username" 
                    label="Nom d'utilisateur" 
                    type="text" 
                    placeholder="johndoe"
                >
                    <x-slot:description>Lettres, chiffres, tirets et underscores (3 caractères min)</x-slot:description>
                </flux:input>
                
                <flux:input 
                    wire:model="phone" 
                    label="Téléphone" 
                    type="tel" 
                    placeholder="xxxxxxxxxx"
                />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:input 
                    wire:model="password" 
                    label="Mot de passe *" 
                    type="password" 
                    required 
                    autocomplete="new-password"
                >
                    <x-slot:description>Minimum 8 caractères avec majuscule, minuscule et chiffre</x-slot:description>
                </flux:input>
                
                <flux:input 
                    wire:model="password_confirmation" 
                    label="Confirmer le mot de passe *" 
                    type="password" 
                    required 
                />
            </div>

            <div class="text-xs text-gray-500 mt-4">
                <p>En créant votre compte, vous acceptez nos conditions d'utilisation et notre politique de confidentialité.</p>
            </div>

            <flux:button 
                type="submit" 
                variant="primary" 
                class="w-full"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>Créer mon compte</span>
                <span wire:loading>
                    Création en cours...
                </span>
            </flux:button>
        </form>
    </div>
</div>