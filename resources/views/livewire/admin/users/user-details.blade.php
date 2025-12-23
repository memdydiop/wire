<?php

use Livewire\Volt\Component;
use App\Models\User;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public ?User $user = null;
    public $showModal = false;
    public string $activeTab = 'overview';

    #[On('open-user-details')]
    public function openModal($userId)
    {
        $this->loadUser($userId);
        $this->activeTab = 'overview'; // Réinitialiser l'onglet à l'ouverture
        $this->showModal = true;
    }

    #[On('user-updated')]
    public function refresh() 
    {
        // Recharger les données si un enfant (ex: RolesTab) a modifié l'user
        if ($this->user) {
            $this->loadUser($this->user->id);
        }
    }

    public function loadUser(int $userId)
    {
        // "Eager loading" des relations pour éviter les requêtes N+1 dans les onglets
        $this->user = User::with(['roles', 'permissions'])->findOrFail($userId);
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // --- ACTIONS GLOBALES (Header) ---

    public function suspendUser()
    {
        if (!$this->user || !$this->user->canBeSuspended()) {
            flash()->error("Action non autorisée.");
            return;
        }

        try {
            $this->user->suspend();
            $this->dispatch('user-updated'); // Met à jour la liste principale
            flash()->warning("Utilisateur suspendu.");
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }
    }

    public function unSuspendUser()
    {
        if (!$this->user) return;
        
        try {
            $this->user->unSuspend();
            $this->dispatch('user-updated');
            flash()->success("Utilisateur réactivé.");
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }
    }

    // --- CONFIGURATION DES ONGLETS ---

    public function getTabsProperty()
    {
        // On peut conditionner l'affichage des onglets ici selon les droits de l'admin connecté
        $tabs = [
            'overview' => [
                'label' => 'Vue d\'ensemble',
                'icon' => 'user',
            ],
            'roles' => [
                'label' => 'Rôles',
                'icon' => 'shield-check',
            ],
            'permissions' => [
                'label' => 'Permissions',
                'icon' => 'key',
            ],
        ];

        // L'onglet sécurité est sensible, on peut le restreindre aux super-admins si besoin
        if (auth()->user()->can('manage users')) {
            $tabs['security'] = [
                'label' => 'Sécurité',
                'icon' => 'lock-closed',
            ];
        }

        return $tabs;
    }
}; ?>

<flux:modal wire:model.self="showModal" name="user-details" class="max-w-5xl! w-full p-0 overflow-hidden" flyout>
    
    @if ($this->user)
        <div class="flex flex-col h-full bg-white">
            
            {{-- === HEADER PROFILE === --}}
            <div class="relative shrink-0">
                {{-- Background Banner --}}
                <div class="absolute inset-0 bg-zinc-900 h-40">
                    {{-- Fallback pattern ou image --}}
                    <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#4f46e5 1px, transparent 1px); background-size: 24px 24px;"></div>
                    <div class="absolute inset-0 bg-gradient-to-b from-transparent to-zinc-900/90"></div>
                </div>

                <div class="relative pt-20 px-8 pb-6 text-white flex flex-col md:flex-row items-end md:items-center gap-6">
                    
                    {{-- Avatar avec indicateur de statut --}}
                    <div class="relative shrink-0">
                        <div class="size-28 rounded-full ring-4 ring-white bg-white overflow-hidden shadow-xl">
                            <flux:avatar :src="$user->avatar()" size="xl" class="size-full" />
                        </div>
                        
                        <div @class([
                            'absolute bottom-1 right-1 p-1.5 rounded-full border-2 border-white shadow-sm',
                            'bg-green-500' => $user->isActive(),
                            'bg-red-500' => !$user->isActive(),
                        ]) title="{{ $user->isActive() ? 'Actif' : 'Suspendu' }}">
                        </div>
                    </div>

                    {{-- Informations principales --}}
                    <div class="flex-1 min-w-0 pb-1">
                        <div class="flex items-center gap-3">
                            <h2 class="text-3xl font-bold truncate">{{ $user->name }}</h2>
                            @if($user->hasAdminRole())
                                <flux:badge color="purple" size="sm" icon="shield-check">Admin</flux:badge>
                            @endif
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-zinc-300 mt-2">
                            <div class="flex items-center gap-1.5">
                                <flux:icon.envelope variant="micro" class="text-zinc-400" />
                                {{ $user->email }}
                            </div>
                            <div class="flex items-center gap-1.5">
                                <flux:icon.map-pin variant="micro" class="text-zinc-400" />
                                {{ $user->city ?? 'N/A' }} {{ $user->country ? '('.$user->country.')' : '' }}
                            </div>
                            <div class="flex items-center gap-1.5">
                                <flux:icon.calendar variant="micro" class="text-zinc-400" />
                                Membre depuis {{ $user->created_at->format('M Y') }}
                            </div>
                        </div>
                    </div>

                    {{-- Actions Rapides (Suspendre/Activer) --}}
                    <div class="flex gap-2 shrink-0 mb-2">
                        @if ($user->canBeModified())
                            @if ($user->isActive())
                                <flux:button variant="danger" size="sm" icon="lock-closed" wire:click="suspendUser" wire:confirm="Voulez-vous vraiment suspendre cet utilisateur ?">
                                    Suspendre
                                </flux:button>
                            @else
                                <flux:button variant="success" size="sm" icon="lock-open" wire:click="unSuspendUser">
                                    Réactiver
                                </flux:button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- === NAVIGATION ONGLETS === --}}
            <div class="px-8 border-b border-zinc-200 bg-white sticky top-0 z-10 shadow-sm">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    @foreach ($this->tabs as $key => $tab)
                        <button 
                            wire:click="switchTab('{{ $key }}')"
                            @class([
                                'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all',
                                'border-primary-600 text-primary-600' => $activeTab === $key,
                                'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' => $activeTab !== $key,
                            ])>
                            <flux:icon :name="$tab['icon']" variant="{{ $activeTab === $key ? 'solid' : 'outline' }}" 
                                @class(['mr-2.5 size-4 transition-colors', 'text-primary-600' => $activeTab === $key, 'text-zinc-400 group-hover:text-zinc-500' => $activeTab !== $key]) 
                            />
                            <span>{{ $tab['label'] }}</span>
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- === CONTENU ONGLETS === --}}
            <div class="p-8 bg-zinc-50/50 min-h-[400px]">
                
                {{-- Spinner de chargement lors du changement d'onglet --}}
                <div wire:loading wire:target="switchTab" class="w-full py-12 flex flex-col items-center justify-center text-zinc-400">
                    <flux:icon.loading class="animate-spin size-8 mb-2" />
                    <span class="text-sm">Chargement...</span>
                </div>

                {{-- Affichage conditionnel des composants enfants --}}
                <div wire:loading.remove wire:target="switchTab">
                    @switch($activeTab)
                        @case('overview')
                            {{-- Key est important pour forcer le refresh si l'ID change --}}
                            <livewire:admin.users.user.overview-tab :user="$user" :key="'ov-'.$user->id" />
                            @break
                        
                        @case('roles')
                            <livewire:admin.users.user.roles-tab :user="$user" :key="'roles-'.$user->id" />
                            @break
                        
                        @case('permissions')
                            <livewire:admin.users.user.permissions-tab :user="$user" :key="'perm-'.$user->id" />
                            @break

                        @case('security')
                            <livewire:admin.users.user.security-tab :user="$user" :key="'sec-'.$user->id" />
                            @break
                    @endswitch
                </div>
            </div>

        </div>
    @endif
</flux:modal>