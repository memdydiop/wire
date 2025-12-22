<?php

use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user->load('roles.permissions');
    }
}; ?>

<div>
    <h3 class="text-lg font-semibold mb-4">Rôles de l'utilisateur</h3>

    @if($user->roles->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($user->roles as $role)
                <div class="border border-zinc-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h4 class="text-base font-semibold text-zinc-900">{{ $role->name }}</h4>
                            @if($role->description)
                                <p class="text-sm text-zinc-500 mt-1">{{ $role->description }}</p>
                            @endif
                        </div>
                        @can('manage roles')
                            <button class="text-zinc-400 hover:text-red-600"
                                onclick="confirm('Retirer ce rôle ?') || event.stopImmediatePropagation()">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endcan
                    </div>

                    @if($role->permissions->count() > 0)
                        <div class="mt-3 pt-3 border-t border-zinc-100">
                            <p class="text-xs font-medium text-zinc-700 mb-2">
                                Permissions ({{ $role->permissions->count() }})
                            </p>
                            <div class="flex flex-wrap gap-1">
                                @foreach($role->permissions->take(5) as $permission)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700">
                                        {{ $permission->name }}
                                    </span>
                                @endforeach
                                @if($role->permissions->count() > 5)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700">
                                        +{{ $role->permissions->count() - 5 }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-zinc-900">Aucun rôle</h3>
            <p class="mt-1 text-sm text-zinc-500">Cet utilisateur n'a aucun rôle assigné.</p>
            @can('manage roles')
                <div class="mt-6">
                    <flux:button>Assigner un rôle</flux:button>
                </div>
            @endcan
        </div>
    @endif
</div>