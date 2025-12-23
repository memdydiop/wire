<?php

use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public User $user;
    
    public function mount(User $user): void { $this->user = $user; }

    // Calcul de la complétion du profil (exemple simple)
    public function getProfileCompletionProperty() {
        $fields = ['name', 'email', 'phone', 'avatar_url', 'city', 'country'];
        $filled = 0;
        foreach($fields as $field) {
            if(!empty($this->user->$field)) $filled++;
        }
        return round(($filled / count($fields)) * 100);
    }
}; ?>

<div class="space-y-8">
    
    {{-- Section Statut & Complétion --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-4 rounded-lg border border-zinc-200 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-full">
                <flux:icon.chart-bar variant="mini" />
            </div>
            <div>
                <p class="text-xs text-zinc-500 font-medium uppercase">Complétion profil</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xl font-bold text-zinc-900">{{ $this->profileCompletion }}%</span>
                    <div class="w-16 h-1.5 bg-zinc-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500" style="width: {{ $this->profileCompletion }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg border border-zinc-200 shadow-sm flex items-center gap-4">
            <div @class(['p-3 rounded-full', 'bg-green-50 text-green-600' => $user->isActive(), 'bg-red-50 text-red-600' => !$user->isActive()])>
                <flux:icon name="{{ $user->isActive() ? 'check-circle' : 'no-symbol' }}" variant="mini" />
            </div>
            <div>
                <p class="text-xs text-zinc-500 font-medium uppercase">État du compte</p>
                <p class="text-xl font-bold text-zinc-900 mt-1">{{ $user->isActive() ? 'Actif' : 'Suspendu' }}</p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg border border-zinc-200 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-purple-50 text-purple-600 rounded-full">
                <flux:icon.clock variant="mini" />
            </div>
            <div>
                <p class="text-xs text-zinc-500 font-medium uppercase">Ancienneté</p>
                <p class="text-xl font-bold text-zinc-900 mt-1">{{ $user->created_at->diffForHumans(null, true) }}</p>
            </div>
        </div>
    </div>

    <flux:separator />

    {{-- Informations Détaillées --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
        
        {{-- Colonne Identité --}}
        <div class="space-y-4">
            <h4 class="font-medium text-zinc-900 flex items-center gap-2">
                <flux:icon.user variant="micro" class="text-zinc-400" /> Identité
            </h4>
            
            <div class="grid grid-cols-1 gap-4 bg-zinc-50 p-4 rounded-lg border border-zinc-200/50">
                <div>
                    <flux:label size="sm">Nom complet</flux:label>
                    <div class="font-medium text-zinc-800">{{ $user->name }}</div>
                </div>
                <div>
                    <flux:label size="sm">Email</flux:label>
                    <div class="flex items-center gap-2">
                        <span class="text-zinc-800">{{ $user->email }}</span>
                        @if($user->email_verified_at)
                            <flux:icon.check-badge class="text-green-500 size-4" title="Email vérifié" />
                        @else
                            <flux:icon.exclamation-triangle class="text-yellow-500 size-4" title="Non vérifié" />
                        @endif
                    </div>
                </div>
                <div>
                    <flux:label size="sm">Téléphone</flux:label>
                    <div class="text-zinc-800">{{ $user->phone ?? '—' }}</div>
                </div>
            </div>
        </div>

        {{-- Colonne Sécurité & Localisation --}}
        <div class="space-y-4">
            <h4 class="font-medium text-zinc-900 flex items-center gap-2">
                <flux:icon.finger-print variant="micro" class="text-zinc-400" /> Connexion & Sécurité
            </h4>

            <div class="grid grid-cols-1 gap-4 bg-zinc-50 p-4 rounded-lg border border-zinc-200/50">
                <div>
                    <flux:label size="sm">Dernière IP connue</flux:label>
                    <div class="font-mono text-sm text-zinc-700">{{ $user->last_login_ip ?? 'Aucune donnée' }}</div>
                </div>
                <div>
                    <flux:label size="sm">Dernière connexion</flux:label>
                    <div class="text-zinc-800">{{ $user->last_login_at?->format('d/m/Y à H:i:s') ?? 'Jamais' }}</div>
                </div>
                <div>
                    <flux:label size="sm">Localisation (Profil)</flux:label>
                    <div class="text-zinc-800">{{ $user->city }} {{ $user->country ? '('.$user->country.')' : '' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>