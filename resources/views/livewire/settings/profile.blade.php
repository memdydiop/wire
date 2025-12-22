<?php

use Livewire\Volt\Component;
use App\Models\User;
use Illuminate\Support\Facades\{Auth, Session, Storage};
use Livewire\Attributes\{Url, Computed, On};
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Url]
    public $activeTab = 'general';

    public string $name = '';
    public string $email = '';
    public string $username = '';
    public string $phone = '';
    public string $address = '';
    public string $city = '';
    public string $country = '';

    public $avatar_url = '';

    public $avatar = null;

    /**
     * Mount the component.
     */
    public function mount(): void
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


    #[On('profile-updated')] 
    public function refresh(){

    }

    #[Computed]
    public function user()
    {
        return Auth::user()->load('roles');
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function tabs()
    {
        return [
            'general' => [
                'label' => 'Informations personnelles',
                'icon' => 'user',
                'description' => 'Gérez vos informations personnelles'
            ],
            'security' => [
                'label' => 'Sécurité',
                'icon' => 'lock-closed',
                'description' => 'Mot de passe et authentification'
            ],
            'activity' => [
                'label' => 'Activité',
                'icon' => 'chart-bar',
                'description' => 'Historique de vos connexions'
            ],
        ];
    }

    /**
     * Update the profile information for the currently authenticated user.
    
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
        flash()->success('Votre profil a été mis à jour avec succès.');
    } */

    /**
     * Upload and save the user's avatar.
     */
    public function uploadAvatar(): void
    {

        $user = Auth::user();
        
        $this->validate([
            'avatar' => ['required', 'image', 'max:2048', 'mimes:jpeg,jpg,png,gif,webp', 'dimensions:min_width=100,min_height=100'],
        ]);
        try {

            // Delete old avatar if exists
            if ($user->avatar_url && Storage::exists($user->avatar_url)) {
                Storage::delete($user->avatar_url);
            }

            // Store new avatar
            $path = $this->avatar->store('avatars', 'public');
            $user->avatar_url = $path;
            $user->save();

            $this->avatar = null;
            $this->dispatch('avatar-updated', name: $user->name);
            flash()->success('Votre photo de profil a été mise à jour avec succès.');
        } catch (\Exception $e) {
            flash()->error('Erreur lors de la mise à jour de votre photo de profil: '.$e->getMessage());
        }
    }

    /**
     * Delete the user's avatar.
     */
    public function deleteAvatar(): void
    {
        $user = Auth::user();
        try {
            if ($user->avatar_url) {
                Storage::disk('public')->delete($user->avatar_url);
                $user->update(['avatar_url' => null]);

                $user->refresh();

                flash()->warning('Votre photo de profil a été supprimée avec succès.');
            } else {
                flash()->info('Aucun avatar à supprimer.');
            }
        } catch (\Exception $e) {
            flash()->error('Erreur lors de la suppression de votre photo de profil: '.$e->getMessage());
        }
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<x-layouts.app.content :title="__('Profile')" :heading="__('Update your name and email address')">

    <div class="grid grid-cols-12 gap-4">
        <div class="card col-span-12 sm:col-span-4 xl:col-span-3">

            <flux:heading level="3" size="md" class="pb-1">Informations Personelles</flux:heading>
            <flux:separator variant="subtle" class=""/>

            <div class="space-y-2 py-4">
                <flux:subheading class="text-center pb-1">Photo de profile</flux:subheading>

                <div class="flex flex-col items-center gap-2">

                    <div class="flex flex-col items-center gap-2">

                        <div class="relative size-48 flex items-center justify-center">
                            <!-- Indicateur de chargement -->
                            <div wire:loading wire:target="avatar"
                                class="z-10 absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                <flux:icon.loading class="text-white size-10" />
                            </div>
                            @if (!$avatar)
                            <!-- Zone de click pour upload -->
                            <flux:tooltip content="Choisir une image">
                                <label  
                                    class="z-10 cursor-pointer absolute bottom-0.5 right-0.5 bg-slate-300/50 rounded-full size-10">
                                    <!-- Icône caméra -->
                                    <flux:icon.camera variant="solid"
                                        class="bg-slate-300/50 text-primary rounded-full size-10 p-2" />
                                    <input type="file" wire:model="avatar"
                                        accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="hidden"
                                        id="avatar-upload">
                                </label>
                            </flux:tooltip>
                            @else
                            <!-- Zone de click pour enregistrer/valider l'upload -->
                                <div class="z-10 cursor-pointer absolute bottom-0.5 right-0.5 bg-slate-300/50 rounded-full size-10 flex items-center justify-center">
                                    <flux:button tooltip="Valider le changement" square variant="ghost" size="xs" wire:click="uploadAvatar"
                                        class="">
                                        <flux:icon.check variant="micro" class=" text-success size-6 " />
                                    </flux:button>
                                </div>
                            @endif

                            <!-- Zone de click pour supprimer -->
                            @if ($this->user->avatar_url && !$avatar)
                                    <div
                                        class="z-10 cursor-pointer absolute bottom-0.5 left-0.5 bg-slate-300/50 rounded-full size-10 flex items-center justify-center ">
                                        <flux:button tooltip="Supprimer l'avatar" square variant="ghost" size="xs" wire:click="deleteAvatar"
                                            class="">
                                            <flux:icon.trash variant="micro" class=" text-danger size-6 " />
                                        </flux:button>
                                    </div>
                            @endif

                            <!-- Zone de click pour annuler -->
                            @if ($avatar)
                                    <div
                                        class="z-10 cursor-pointer absolute bottom-0.5 left-0.5 bg-slate-300/50 rounded-full size-10 flex items-center justify-center ">
                                        <flux:button tooltip="Annuler le changement" square variant="ghost" size="xs" wire:click="$set('avatar', null)"
                                            class="">
                                            <flux:icon.x-mark variant="micro" class=" text-danger size-6 " />
                                        </flux:button>
                                    </div>
                            @endif

                            <!-- Avatar -->
                            <div class="size-48 bg-primary/20 mask mask-squircle absolute inset-0"></div>
                            @if ($avatar)
                                <!-- Preview du nouvel avatar -->
                                <img src="{{ $avatar->temporaryUrl() }}" alt="Avatar preview"
                                    class="size-44 mask mask-squircle bg-primary bg-gradient object-cover" />
                            @elseif ($this->user->avatar_url)
                                <!-- Avatar existant -->
                                <img src="{{ Storage::url($this->user->avatar_url) }}" alt="Avatar"
                                    class="size-44 mask mask-squircle bg-primary bg-gradient object-cover" />
                            @else
                                <!-- Avatar par défaut (initiales) -->
                                <flux:avatar name="{{ $this->user->name }}"
                                    class="size-44 font-semibold text-white text-7xl bg-primary bg-gradient" />
                            @endif

                        </div>

                        <div class="text-center">
                            <flux:heading level="3" size="lg">
                                {{ $this->user->name }}
                            </flux:heading>
                            <flux:text class="text-sm text-gray-600 mt-0">
                                {{ $this->user->getUsername() }}
                            </flux:text>
                        </div>
                    </div>

                </div>

            </div>

            <div class="grid sm:grid-cols-1 grid-cols-2 gap-2 border-t border-dashed py-4">
                <flux:subheading level="3" size="sm" class="text-center pb-1">Informations de base</flux:subheading>
                <div class="flex flex-col items-start">
                    <flux:text class="font-medium">Nom :</flux:text>
                    <flux:text class="text-sm text-slate-600 font-medium">{{ $this->user->name }}</flux:text>
                </div>
                <div class="flex flex-col items-start">
                    <flux:text class="font-medium">Email :</flux:text>
                    <flux:text class="text-sm text-slate-600 font-medium">{{ $this->user->email }}</flux:text>
                </div>
                <div class="flex flex-col items-start">
                    <flux:text class="font-medium">Telephone :</flux:text>
                    <flux:text class="text-sm text-slate-600 font-medium">{{ $this->user->phone }}</flux:text>
                </div>
                <div class="flex flex-col items-start">
                    <flux:text class="font-medium">Adresse :</flux:text>
                    <flux:text class="text-sm text-slate-600 font-medium">{{ $this->user->address }}</flux:text>
                </div>
                <div class="flex flex-col items-start">
                    <flux:text class="font-medium">Ville :</flux:text>
                    <flux:text class="text-sm text-slate-600 font-medium">{{ $this->user->city }}</flux:text>
                </div>
                <div class="flex flex-col items-start">
                    <flux:text class="font-medium">Pays :</flux:text>
                    <flux:text class="text-sm text-slate-600 font-medium">{{ $this->user->country }}</flux:text>
                </div>

            </div>


        </div>

        <div class="card p-0! col-span-12 sm:col-span-8 xl:col-span-9">
            <!-- Navigation par onglets -->
            <div class="">
                <nav class="h-12 flex gap-px mb-0 px-2 border-b-4 border-primary/30 " aria-label="Tabs">
                    @foreach($this->tabs() as $key => $tab)
                        <div wire:click="setActiveTab('{{ $key }}')" 
                            @class([
                                'relative group',
                                'flex items-center justify-center gap-1 px-4 py-2 text-sm transition-all duration-200',
                                'after:h-1 after:transition-all after:duration-300 after:ease-in-out after:delay-0',
                                'after:absolute after:-bottom-1 after:left-0 after:bg-primary',
                                'after:w-full after:scale-x-0',
                                'text-primary after:scale-x-100' => $activeTab === $key,
                                ])
                                role="tab" aria-selected="{{ $activeTab === 'general' ? 'true' : 'false' }}">
                                <flux:icon name="{{ $tab['icon'] }}" 
                                variant="mini" 
                                @class([
                                    'group-hover:transition-all group-hover:duration-200',
                                    'text-primary' => $activeTab === $key,
                                    'text-gray-400 group-hover:text-primary' => $activeTab !== $key,
                                ])
                                />
                            <span class="truncate">{{ $tab['label'] }}</span>
                        </div>
                    @endforeach
                </nav>
            </div>
            
            <div class="p-4">
                
                
                    @switch($activeTab)
                        @case('general')
                            <livewire:settings.profile.general-info :user="$this->user" />
                        @break

                        @case('security')
                            <livewire:settings.profile.security :user="$this->user" />
                        @break

                        @case('activity')
                            <livewire:settings.profile.activity :user="$this->user" />
                        @break
                    @endswitch
                
            </div>
        </div>
    </div>

</x-layouts.app.content>
