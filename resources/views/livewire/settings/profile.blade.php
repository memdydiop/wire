<?php

use Livewire\Volt\Component;
use App\Models\User;
use Illuminate\Support\Facades\{Auth, Storage};
use Livewire\Attributes\{Url, Computed, On};
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Url(as: 'tab')] // Alias pour l'URL ?tab=general
    public $activeTab = 'general';

    public $avatar = null;

    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    #[On('profile-updated')]
    public function refreshUser() {
        unset($this->user);
    }

    public function uploadAvatar(): void
    {
        $this->validate([
            'avatar' => ['required', 'image', 'max:5120', 'mimes:jpeg,jpg,png,webp'],
        ]);

        try {
            $user = Auth::user();

            // Nettoyage de l'ancienne image si elle existe
            if ($user->avatar_url && Storage::disk('public')->exists($user->avatar_url)) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            // Stockage avec un nom haché
            $path = $this->avatar->store('avatars', 'public');
            $user->update(['avatar_url' => $path]);

            $this->reset('avatar'); // Reset propre de la variable
            $this->dispatch('profile-updated');
            
            // Si vous n'avez pas de package 'flash', utilisez session()->flash
            flash()->success('Photo mise à jour avec succès.'); 
        } catch (\Exception $e) {
            flash()->error("Erreur lors de l'envoi de l'image.");
        }
    }

    public function deleteAvatar(): void
    {
        $user = Auth::user();
        if ($user->avatar_url) {
            if (Storage::disk('public')->exists($user->avatar_url)) {
                Storage::disk('public')->delete($user->avatar_url);
            }
            $user->update(['avatar_url' => null]);
            $this->dispatch('profile-updated');
            flash()->info('Photo de profil supprimée.');
        }
    }

    public function setActiveTab($tab) { 
        // Vérification de sécurité simple pour éviter qu'on passe n'importe quoi
        if(array_key_exists($tab, $this->tabs())) {
            $this->activeTab = $tab; 
        }
    }

    public function tabs() {
        return [
            'general'  => ['label' => 'Général', 'icon' => 'user-circle'],
            'security' => ['label' => 'Sécurité', 'icon' => 'lock-closed'],
            'activity' => ['label' => 'Activité', 'icon' => 'clock'],
        ];
    }
}; ?>

<x-layouts.app.content title="Mon Profil">
    
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <div class="lg:col-span-4 xl:col-span-3 flex flex-col gap-6">
            <div class="card bg-white border border-gray-200 rounded-xl p-6 flex flex-col items-center text-center relative overflow-hidden shadow-sm">
                <div class="absolute top-0 left-0 w-full h-24 bg-gradient-to-r from-blue-50 to-blue-100"></div>

                <div class="relative mt-8 mb-4 group">
                    <div class="relative size-32 rounded-full ring-4 ring-white shadow-lg overflow-hidden bg-white">
                        @if ($avatar)
                            <img src="{{ $avatar->temporaryUrl() }}" class="w-full h-full object-cover">
                        @elseif ($this->user->avatar_url)
                             <img src="{{ Storage::url($this->user->avatar_url) }}" class="w-full h-full object-cover" alt="Avatar">
                        @else
                             <flux:avatar name="{{ $this->user->name }}" variant="solid" class="size-full text-3xl" />
                        @endif
                    </div>

                    <div class="absolute inset-0 rounded-full bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <label class="cursor-pointer p-2 text-white hover:text-blue-200 transition" title="Changer la photo">
                            <flux:icon.camera class="size-6" />
                            <input type="file" wire:model="avatar" class="hidden" accept="image/*">
                        </label>
                        @if($this->user->avatar_url || $avatar)
                            <button wire:click="deleteAvatar" class="p-2 text-white hover:text-red-400 transition" title="Supprimer la photo">
                                <flux:icon.trash class="size-6" />
                            </button>
                        @endif
                    </div>
                    
                    <div wire:loading wire:target="avatar" class="absolute inset-0 rounded-full bg-black/50 flex items-center justify-center">
                        <flux:icon.loading class="text-white animate-spin size-8" />
                    </div>
                </div>

                @if($avatar)
                    <div class="flex gap-2 mb-4">
                        <flux:button size="xs" wire:click="uploadAvatar" variant="primary">Enregistrer</flux:button>
                        <flux:button size="xs" wire:click="$set('avatar', null)">Annuler</flux:button>
                    </div>
                @endif

                <flux:heading size="lg">{{ $this->user->name }}</flux:heading>
                <flux:text variant="subtle" class="mb-4">{{ $this->user->email }}</flux:text>
                
                {{-- Gestion des rôles avec Spatie Permission si installé --}}
                @if(method_exists($this->user, 'getRoleNames'))
                    <flux:badge color="zinc" size="sm">{{ $this->user->getRoleNames()->first() ?? 'Utilisateur' }}</flux:badge>
                @endif

                <flux:separator variant="subtle" class="my-6 w-full" />

                <div class="w-full space-y-3 text-left">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Membre depuis</span>
                        <span class="font-medium">{{ $this->user->created_at->translatedFormat('M Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Ville</span>
                        <span class="font-medium">{{ $this->user->city ?: '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-8 xl:col-span-9 space-y-6">
            
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    @foreach($this->tabs() as $key => $tab)
                        <button 
                            wire:click="setActiveTab('{{ $key }}')"
                            @class([
                                'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all outline-none focus:outline-none',
                                'border-blue-600 text-blue-600' => $activeTab === $key,
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' => $activeTab !== $key,
                            ])>
                            <flux:icon :name="$tab['icon']" variant="{{ $activeTab === $key ? 'solid' : 'outline' }}" 
                                class="mr-2 -ml-0.5 size-5 {{ $activeTab === $key ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500' }}" 
                            />
                            {{ $tab['label'] }}
                        </button>
                    @endforeach
                </nav>
            </div>

            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl md:col-span-2">
                <div class="px-4 py-6 sm:p-8">
                    {{-- Note: Assurez-vous que les fichiers sont dans resources/views/livewire/settings/profile/ --}}
                    @switch($activeTab)
                        @case('general')
                            <livewire:settings.profile.general-info />
                        @break
                        @case('security')
                            <livewire:settings.profile.security />
                        @break
                        @case('activity')
                            <livewire:settings.profile.activity />
                        @break
                    @endswitch
                </div>
            </div>

        </div>
    </div>
</x-layouts.app.content>