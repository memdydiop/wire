<?php

use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user;
    }
}; ?>

<div>
    <h3 class="text-lg font-semibold mb-4">Informations générales</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-zinc-700 mb-1">Nom complet</label>
            <p class="text-zinc-900">{{ $user->name }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-zinc-700 mb-1">Email</label>
            <p class="text-zinc-900">{{ $user->email }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-zinc-700 mb-1">Statut</label>
            <p>
                @if($user->isActive())
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Actif
                    </span>
                @else
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        Suspendu
                    </span>
                @endif
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-zinc-700 mb-1">Date de création</label>
            <p class="text-zinc-900">{{ $user->created_at->format('d/m/Y H:i') }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-zinc-700 mb-1">Dernière mise à jour</label>
            <p class="text-zinc-900">{{ $user->updated_at->format('d/m/Y H:i') }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-zinc-700 mb-1">Rôles</label>
            <div class="flex flex-wrap gap-2">
                @forelse($user->roles as $role)
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary">
                        {{ $role->name }}
                    </span>
                @empty
                    <span class="text-zinc-500 text-sm">Aucun rôle assigné</span>
                @endforelse
            </div>
        </div>
    </div>
</div>