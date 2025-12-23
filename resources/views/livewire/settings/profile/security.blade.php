<?php
use Livewire\Volt\Component;
use Illuminate\Support\Facades\{Auth, Hash, DB};
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\{ConfirmTwoFactorAuthentication, DisableTwoFactorAuthentication, EnableTwoFactorAuthentication};
use Laravel\Fortify\Features;

new class extends Component {
    // Password State
    public $current_password = '';
    public $password = '';
    public $password_confirmation = '';

    // 2FA State
    public bool $twoFactorEnabled = false;
    public bool $qrCodeShown = false;
    public $qrCodeSvg = null;
    public $setupKey = null;
    public $code = '';
    
    // Session Management State
    public $passwordForSessions = '';

    public function mount()
    {
        // On vérifie si la 2FA est activée ET confirmée (Fortify gère 'confirmed_at' dans les versions récentes)
        // Pour une implémentation simple, on vérifie juste la présence du secret
        $this->twoFactorEnabled = !empty(Auth::user()->two_factor_secret) && !empty(Auth::user()->two_factor_confirmed_at);
    }

    // --- PASSWORD LOGIC ---
    public function updatePassword()
    {
        try {
            $this->validate([
                'current_password' => ['required', 'current_password'], // Règle native Laravel
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            Auth::user()->update([
                'password' => Hash::make($this->password),
            ]);

            $this->reset(['current_password', 'password', 'password_confirmation']);
            flash()->success('Mot de passe modifié avec succès.');
        } catch (ValidationException $e) {
            throw $e;
        }
    }

    // --- 2FA LOGIC ---
    public function enableTwoFactor(EnableTwoFactorAuthentication $enable)
    {
        // 1. Active la 2FA (génère le secret dans la DB)
        $enable(Auth::user());
        
        // 2. Affiche le QR code pour la configuration
        $this->showQrCode();
    }

    public function showQrCode()
    {
        // On force le rechargement de l'utilisateur pour avoir le secret fraîchement généré
        $user = Auth::user()->fresh(); 

        $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
        $this->setupKey = decrypt($user->two_factor_secret);
        
        $this->qrCodeShown = true;
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirm)
    {
        $this->validate(['code' => 'required|string|size:6']);
        
        try {
            // Confirme que le code est bon et set 'two_factor_confirmed_at'
            $confirm(Auth::user(), $this->code);
            
            $this->qrCodeShown = false; // Plus besoin du QR code
            $this->twoFactorEnabled = true; // UI update
            $this->code = '';
            
            flash()->success('Authentification à deux facteurs activée et confirmée.');
        } catch (ValidationException $e) {
            $this->addError('code', 'Le code de vérification est invalide.');
        } catch (\Exception $e) {
            $this->addError('code', 'Erreur lors de la confirmation.');
        }
    }

    public function disableTwoFactor(DisableTwoFactorAuthentication $disable)
    {
        $disable(Auth::user());
        
        $this->twoFactorEnabled = false;
        $this->qrCodeShown = false;
        $this->qrCodeSvg = null;
        $this->setupKey = null;
        
        flash()->warning('Authentification à deux facteurs désactivée.');
    }

    // --- SESSION LOGIC ---
    public function logoutOtherBrowserSessions()
    {
        $this->validate([
            'passwordForSessions' => ['required', 'current_password'],
        ]);

        // Cette méthode invalide les sessions sur les autres appareils
        Auth::logoutOtherDevices($this->passwordForSessions);
        
        $this->passwordForSessions = '';
        $this->dispatch('close-modal', name: 'logout-sessions'); // Fermer la modale proprement
        
        flash()->success('Toutes les autres sessions ont été déconnectées.');
    }
}; 
?>

<div>
    <div class="mb-6">
        <flux:heading level="2">Sécurité</flux:heading>
        <flux:subheading>Gérez votre mot de passe et l'authentification double facteur.</flux:subheading>
    </div>

    <div class="space-y-8">
        
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1">
                <flux:heading level="3" size="sm">Mot de passe</flux:heading>
                <p class="text-sm text-gray-500 mt-1">Choisissez un mot de passe robuste d'au moins 8 caractères.</p>
            </div>
            <div class="md:col-span-2 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <form wire:submit="updatePassword" class="space-y-4">
                    <flux:input wire:model="current_password" type="password" label="Mot de passe actuel" required />
                    <flux:input wire:model="password" type="password" label="Nouveau mot de passe" required />
                    <flux:input wire:model="password_confirmation" type="password" label="Confirmer le mot de passe" required />

                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary">Enregistrer</flux:button>
                    </div>
                </form>
            </div>
        </section>

        <flux:separator />

        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1">
                <flux:heading level="3" size="sm">Double Authentification (2FA)</flux:heading>
                <p class="text-sm text-gray-500 mt-1">Ajoutez une couche de sécurité supplémentaire en exigeant un code temporaire à la connexion.</p>
            </div>
            <div class="md:col-span-2 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                @if(!$twoFactorEnabled && !$qrCodeShown)
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">Vous n'avez pas activé la double authentification.</p>
                            <p class="text-sm text-gray-500 mt-1">Protégez votre compte contre les accès non autorisés.</p>
                        </div>
                        <flux:button wire:click="enableTwoFactor" variant="primary">Activer</flux:button>
                    </div>
                @elseif($twoFactorEnabled)
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-medium text-green-700 flex items-center gap-2">
                                <flux:icon.check-circle variant="mini" /> 2FA Activée
                            </p>
                            <p class="text-sm text-gray-500 mt-1">Votre compte est sécurisé.</p>
                        </div>
                        <div class="flex gap-2">
                            {{-- On permet de réafficher le QR Code si besoin de reconfigurer un nouvel appareil --}}
                            @if(!$qrCodeShown)
                                <flux:button wire:click="showQrCode" variant="ghost" size="sm">Afficher QR Code</flux:button>
                            @endif
                            <flux:button wire:click="disableTwoFactor" variant="danger" size="sm">Désactiver</flux:button>
                        </div>
                    </div>
                @endif

                @if($qrCodeShown)
                    <div class="mt-6 border-t border-gray-100 pt-6">
                        <div class="mb-4">
                            <p class="font-medium text-gray-900">Configuration de l'application</p>
                            <p class="text-sm text-gray-500">Scannez ce QR Code avec Google Authenticator ou Authy.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="flex flex-col items-center justify-center bg-gray-50 p-4 rounded-lg border border-gray-200">
                                {!! $qrCodeSvg !!}
                            </div>
                            
                            <div class="space-y-6">
                                <div>
                                    <label class="text-xs font-semibold uppercase text-gray-500 tracking-wider">Clé de configuration (si scan impossible)</label>
                                    <div class="flex gap-2 mt-1">
                                        <code class="bg-gray-100 p-2 rounded text-sm block w-full font-mono text-gray-800 break-all select-all">{{ $setupKey }}</code>
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <form wire:submit="confirmTwoFactor">
                                        <flux:input wire:model="code" label="Code de vérification" placeholder="Ex: 123456" description="Entrez le code à 6 chiffres affiché sur votre application." />
                                        
                                        <div class="mt-4">
                                            <flux:button type="submit" class="w-full" variant="filled">Confirmer et Activer</flux:button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex justify-end">
                             <flux:button wire:click="$set('qrCodeShown', false)" variant="ghost" size="sm">Annuler</flux:button>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <flux:separator />

        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1">
                <flux:heading level="3" size="sm">Sessions Navigateur</flux:heading>
                <p class="text-sm text-gray-500 mt-1">Gérez et déconnectez vos sessions actives sur d'autres navigateurs et appareils.</p>
            </div>
            <div class="md:col-span-2 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <p class="text-sm text-gray-600 mb-4">
                    Si vous pensez que votre compte a été compromis, vous pouvez vous déconnecter de toutes les autres sessions de navigation sur tous vos appareils.
                </p>
                
                <flux:modal.trigger name="logout-sessions">
                    <flux:button variant="filled">Déconnecter les autres sessions</flux:button>
                </flux:modal.trigger>

                <flux:modal name="logout-sessions" class="min-w-[22rem]">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">Déconnecter les autres sessions ?</flux:heading>
                            <flux:subheading>Veuillez entrer votre mot de passe pour confirmer cette action.</flux:subheading>
                        </div>
                        
                        <flux:input wire:model="passwordForSessions" type="password" placeholder="Votre mot de passe" />
                        
                        <div class="flex gap-2 justify-end">
                            <flux:modal.close>
                                <flux:button variant="ghost">Annuler</flux:button>
                            </flux:modal.close>
                            <flux:button wire:click="logoutOtherBrowserSessions" variant="danger">Déconnecter</flux:button>
                        </div>
                    </div>
                </flux:modal>
            </div>
        </section>

    </div>
</div>