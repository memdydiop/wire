<?php

use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user->load('permissions');
    }
}; ?>

<div>
    <h3 class="text-lg font-semibold mb-4">Permissions de l'utilisateur</h3>

    @if($user->getAllPermissions()->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200">
                <thead class="bg-zinc-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                            Permission
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                            Description
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-zinc-200">
                    @foreach($user->getAllPermissions() as $permission)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900">
                                {{ $permission->name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-500">
                                {{ $permission->description ?? 'Aucune description' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @can('manage permissions')
                                    <button class="text-red-600 hover:text-red-900"
                                        onclick="confirm('Retirer cette permission ?') || event.stopImmediatePropagation()">
                                        Retirer
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-zinc-900">Aucune permission</h3>
            <p class="mt-1 text-sm text-zinc-500">Cet utilisateur n'a aucune permission directe assignée.</p>
            @can('manage permissions')
                <div class="mt-6">
                    <flux:button>Ajouter une permission</flux:button>
                </div>
            @endcan
        </div>
    @endif
</div>