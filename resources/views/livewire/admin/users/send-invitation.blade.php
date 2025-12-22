<?php

use App\Models\Invitation;
use App\Notifications\InvitationNotification;
use Livewire\Attributes\{Validate, Computed};
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

new class extends Component {
    
    #[Validate]
    public string $email = '';

    #[Validate('required|exists:roles,name')]
    public string $role = 'user';

    #[Validate('required|integer|min:1|max:30')]
    public int $expiryDays = 7;

    /**
     * Règles de validation personnalisées
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'unique:users,email',
                // Vérifier qu'aucune invitation valide n'existe
                function ($attribute, $value, $fail) {
                    $existingInvitation = Invitation::where('email', $value)
                        ->valid()
                        ->first();
                    
                    if ($existingInvitation) {
                        $fail('Une invitation valide existe déjà pour cet email.');
                    }
                },
            ],
            'role' => 'required|exists:roles,name',
            'expiryDays' => 'required|integer|min:1|max:30',
        ];
    }

    /**
     * Messages de validation personnalisés
     */
    public function messages(): array
    {
        return [
            'email.required' => 'L\'adresse email est requise.',
            'email.email' => 'L\'adresse email n\'est pas valide.',
            'email.unique' => 'Un utilisateur avec cet email existe déjà.',
            'role.required' => 'Le rôle est requis.',
            'role.exists' => 'Le rôle sélectionné n\'existe pas.',
            'expiryDays.required' => 'La durée de validité est requise.',
            'expiryDays.integer' => 'La durée de validité doit être un nombre entier.',
            'expiryDays.min' => 'La durée de validité doit être d\'au moins 1 jour.',
            'expiryDays.max' => 'La durée de validité ne peut pas dépasser 30 jours.',
        ];
    }

    /**
     * Initialisation du composant
     */
    public function mount()
    {
        $this->role = 'user';
        $this->expiryDays = 7;
    }

    /**
     * Réinitialiser le formulaire après fermeture du modal
     */
    public function resetForm()
    {
        $this->reset(['email', 'role', 'expiryDays']);
        $this->resetValidation();
        $this->role = 'user';
        $this->expiryDays = 7;
    }

    /**
     * Envoyer l'invitation
     */
    public function sendInvitation()
    {
        $this->validate();

        try {
            // 🔒 Créer l'invitation avec TOKEN unique
            $invitation = Invitation::createNew(
                email: $this->email,
                role: $this->role,
                sentById: Auth::id(),
                expiryDays: $this->expiryDays
            );

            // 📧 Envoyer l'email avec le token dans le lien
            Notification::route('mail', $this->email)
                ->notify(new InvitationNotification($invitation));

            flash()->success("Invitation envoyée à {$this->email}");

            // Notifier le composant parent pour rafraîchir la liste
            $this->dispatch('invitation-sent');
            
            // Fermer le modal et réinitialiser
            $this->dispatch('close-modal', 'create-invitation');
            $this->resetForm();

        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'envoi de l\'invitation', [
                'email' => $this->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            flash()->error('Erreur lors de l\'envoi de l\'invitation : ' . $e->getMessage());
        }
    }

    /**
     * Liste des rôles disponibles
     */
    #[Computed]
    public function roles()
    {
        return Role::where('name', '!=', 'ghost')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtenir les statistiques des invitations
     */
    #[Computed]
    public function invitationStats()
    {
        return [
            'total' => Invitation::count(),
            'pending' => Invitation::pending()->count(),
            'valid' => Invitation::valid()->count(),
            'expired' => Invitation::expired()->count(),
        ];
    }
}; 
?>

<div>
    <!-- Modal de création d'invitation -->
    <flux:modal name="create-invitation" class="md:w-96 w-80">
        <form wire:submit="sendInvitation" class="space-y-4">
            <div>
                <flux:heading size="lg">Formulaire d'invitation</flux:heading>
                <flux:subheading>
                    Envoyez une invitation par email pour créer un nouveau compte.
                </flux:subheading>
            </div>

            <flux:separator />

            <div class="space-y-4">
                <!-- Email -->
                <flux:input 
                    wire:model.blur="email" 
                    label="Adresse email" 
                    type="email" 
                    placeholder="utilisateur@exemple.com"
                    required 
                    autocomplete="off"
                >
                    <x-slot:iconTrailing>
                        <flux:icon.envelope variant="mini" />
                    </x-slot:iconTrailing>
                </flux:input>

                <!-- Rôle -->
                <flux:select 
                    wire:model.live="role" 
                    label="Rôle" 
                    placeholder="Sélectionner un rôle"
                >
                    @foreach($this->roles as $roleItem)
                        <flux:select.option value="{{ $roleItem->name }}">
                            <div class="flex items-center justify-between w-full">
                                <span>{{ ucfirst($roleItem->name) }}</span>
                            </div>
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <!-- Validité -->
                <flux:input 
                    wire:model.blur="expiryDays" 
                    label="Validité de l'invitation" 
                    type="number" 
                    min="1" 
                    max="30"
                    suffix="jours"
                >
                    <x-slot:description>
                        L'invitation expirera après {{ $expiryDays }} jour{{ $expiryDays > 1 ? 's' : '' }}.
                    </x-slot:description>
                </flux:input>
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
                    icon="paper-airplane"
                >
                    Envoyer l'invitation
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>