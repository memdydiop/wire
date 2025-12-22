<?php

use Livewire\Volt\Component;
use App\Models\User;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public ?User $user = null;
    public $showModal = false;
    public string $activeTab = 'overview';

    // public function mount(User $user, ?string $tab = null): void
    // {
    //     $this->loadUser($user);
    //     $this->activeTab = $tab ?? 'overview';
    // }

    #[On('user-updated')]
    public function refresh() {}

    #[On('open-user-details')]
    public function openModal($userId)
    {
        $this->loadUser($userId);
        $this->showModal = true;
    }

    public function loadUser(int $userId)
    {
        $this->user = User::with(['roles', 'permissions'])->findOrFail($userId);
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->user = null;
    }

    // Helper pour la sécurité
    private function cannotManage($user)
    {
        if ($user->id === Auth::id()) {
            flash()->error('Action impossible sur votre propre compte.');
            return true;
        }
        if ($user->hasAdminRole()) {
            flash()->error('Action impossible sur un administrateur.');
            return true;
        }
        return false;
    }

    // --- ACTIONS UNITAIRES ---
    public function suspendUser($userId)
    {
        try {
            $user = User::findOrFail($userId);
            if ($this->cannotManage($user)) {
                return;
            }

            $user->suspend();
            $this->dispatch('user-updated');
            flash()->warning("Utilisateur {$user->name} suspendu.");
        } catch (\Exception $e) {
            flash()->error('Erreur: ' . $e->getMessage());
        }
    }

    public function unSuspendUser($userId)
    {
        try {
            $user = User::findOrFail($userId);

            $user->unSuspend();
            $this->dispatch('user-updated');
            flash()->success("Utilisateur {$user->name} réactivé.");
        } catch (\Exception $e) {
            flash()->error('Erreur: ' . $e->getMessage());
        }
    }

    public function activateUser()
    {
        $this->user->activate();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getTabContentProperty()
    {
        return match ($this->activeTab) {
            'overview' => $this->renderOverview(),
            'permissions' => $this->renderPermissions(),
            'roles' => $this->renderRoles(),
            default => 'Contenu non disponible',
        };
    }
    public function tabs()
    {
        return [
            'overview' => [
                'label' => 'Info perso',
                'icon' => 'user',
                'description' => 'Gérez vos informations personnelles',
            ],
            'permissions' => [
                'label' => 'Permissions',
                'icon' => 'lock-closed',
                'description' => 'Mot de passe et authentification',
            ],
            'roles' => [
                'label' => 'Rôles',
                'icon' => 'chart-bar',
                'description' => 'Historique de vos connexions',
            ],
        ];
    }

    private function renderOverview(): string
    {
        return view('components.user.tabs.overview', ['user' => $this->user])->render();
    }

    private function renderPermissions(): string
    {
        return view('components.user.tabs.permissions', ['user' => $this->user])->render();
    }

    private function renderRoles(): string
    {
        return view('components.user.tabs.roles', ['user' => $this->user])->render();
    }
}; ?>

<flux:modal wire:model.self="showModal" name="user-details" class="max-w-7xl! md:w-3xl lg:w-5xl! xl:w-7xl!" flyout>
    
    @if ($this->user)
        {{-- Sidebar avec avatar et infos --}}
        <div class="col-span-12 space-y-4">

            <div class="relative overflow-hidden rounded-t-lg">
                <div class="absolute inset-0 bg-[url('../../public/images/4.jpg')] bg-cover bg-center"></div>

                <div @class([
                    'p-6 overflow-hidden bg-secondary/40 shadow-card relative',
                    'flex justify-between items-center gap-4',
                ])>
                    <!-- Avatar + User info -->
                    <div class="z-10 flex flex-col sm:flex-row items-start sm:items-center gap-2">
                        <!--Avatar -->
                        <div class="relative size-28 flex items-center justify-center">
                            <div class="absolute inset-0 bg-light size-28 mask mask-squircle">
                            </div>
                            @if ($user->hasCustomAvatar())
                                <flux:avatar name="{{ $user->name }}" src="{{ Storage::url($user->avatar_url) }}"
                                    class="size-24 bg-primary bg-gradient ring-offset-2 ring-2! ring-danger!" />
                            @else
                                <flux:avatar name="{{ $user->name }}"
                                    class="size-24 text-white text-5xl bg-primary bg-gradient ring-offset-2 ring-2! ring-danger!" />
                            @endif
                        </div>
                        <!--User info-->
                        <div class="flex flex-col items-start">
                            <flux:heading level="2" size="md" class="text-white">{{ $user->name }}
                            </flux:heading>
                            <flux:separator class="mb-1.5" variant="light" />
                            <div class="flex flex-col sm:flex-row">
                                <flux:subheading size="sm" class="truncate text-white">{{ $user->email }}
                                </flux:subheading>
                                <span class="text-gray-500 mx-1 hidden sm:block text-white">•</span>
                                <flux:subheading size="sm" class="text-white">{{ $user->getUsername() }}
                                </flux:subheading>
                            </div>
                            <div class="flex">
                                <flux:text class="text-white"> {{ $user->city }} </flux:text>
                                <span @class([
                                    'hidden',
                                    'text-gray-500 text-white mx-1 !block' => $user->city && $user->country,
                                ])>•</span>
                                <flux:text class="text-white"> {{ $user->country }} </flux:text>
                            </div>
                            @if ($user->last_login_at)
                                <span
                                    class="text-gray-500 text-white">{{ $user->last_login_at->format('d/m/Y H:i:s') }}</span>

                                <span class="text-gray-500 text-white">{{ $user->last_login_ip }}</span>
                            @endif
                        </div>
                    </div>
                    <!-- Actions buttons -->
                    @can('suspend users')
                        @if ($user->id !== Auth::id())
                            <div class="flex flex-col gap-2">

                                @can('suspend users')
                                    @if ($user->isActive())
                                        <flux:button variant="warning" icon="lock-closed"
                                            wire:click="suspendUser({{ $user->id }})">
                                            Suspendre
                                        </flux:button>
                                    @else
                                        <flux:button variant="success" icon="lock-open"
                                            wire:click="unSuspendUser({{ $user->id }})">
                                            Réactiver
                                        </flux:button>
                                    @endif
                                @endcan
                                @can('delete users')
                                    <flux:button variant="danger" icon="trash" iconVariant="micro">
                                        Supprimer
                                    </flux:button>
                                @endcan
                            </div>
                        @else
                            <flux:button variant="info" :href="route('profile.edit')" icon="cog" iconVariant="micro"
                                wire:navigate>
                                Modifier
                            </flux:button>
                        @endif
                    @endcan
                </div>
            </div>
        </div>

        {{-- Contenu principal avec onglets --}}
        <div class="col-span-12">
            <div class="overflow-hidden rounded-b-lg bg-white border">
                {{-- Navigation par onglets --}}
                <div class="p-0 border-b-2 border-primary/20">
                    <nav class="flex items-center" role="tablist">
                        @foreach ($this->tabs() as $key => $tab)
                            <div class="relative flex-1">
                                <button wire:click="switchTab('{{ $key }}')" @class([
                                    'w-full flex items-center justify-center gap-2 bg-transparent px-4 py-2 text-sm rounded-t-lg transition-all duration-200',
                                    'after:transition-all after:duration-500 after:ease-in-out after:delay-0',
                                    'after:absolute after:-bottom-0.5 after:left-0 after:w-full after:h-0.5 after:bg-primary',
                                    'after:w-full after:scale-x-0',
                                    'text-primary! after:scale-x-100 font-medium' => $activeTab === $key,
                                    'text-zinc-600 font-normal' => $activeTab !== $key,
                                    'hover:text-primary hover:font-medium hover:bg-transparent!' =>
                                        $activeTab !== $key,
                                ])
                                    role="tab" aria-selected="{{ $activeTab === 'overview' ? 'true' : 'false' }}">
                                    <flux:icon name="{{ $tab['icon'] }}" variant="micro"
                                        @class([
                                            'text-primary' => $activeTab === $key,
                                            'text-gray-400 group-hover:text-gray-500' => $activeTab !== $key,
                                        ]) />
                                    <span>{{ $tab['label'] }}</span>
                                </button>
                            </div>
                        @endforeach
                    </nav>
                </div>

                {{-- Contenu des onglets --}}
                <div class="p-6 h-[calc(100vh_-_theme(spacing.64))]  overflow-y-auto" wire:loading.class="opacity-50">
                    
                    <div wire:loading wire:target="switchTab" class="text-center py-4">
                        <span class="text-zinc-500">Chargement...</span>
                    </div>

                    <div wire:loading.remove wire:target="switchTab" class="">
                        @switch($activeTab)
                            @case('overview')
                                <livewire:admin.users.user.overview-tab :user="$user" :key="'overview-' . $user->id" />
                            @break

                            @case('permissions')
                                <livewire:admin.users.user.permissions-tab :user="$user" :key="'permissions-' . $user->id" />
                            @break

                            @case('roles')
                                <livewire:admin.users.user.roles-tab :user="$user" :key="'roles-' . $user->id" />
                            @break
                        @endswitch
                    </div>
                </div>
            </div>
        </div>
    @endif

</flux:modal>
