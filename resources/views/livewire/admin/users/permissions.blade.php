<?php

use Livewire\Volt\Component;
use App\Models\User;
use Livewire\Attributes\On;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new class extends Component {
    public ?User $user = null;
    public $selectedRoles = [];
    public $selectedPermissions = [];
    public $showModal = false;
    public $activeTab = 'roles';

    #[On('open-user-permissions')]
    public function openModal($userId)
    {
        $this->loadUser($userId);
        $this->showModal = true;
    }

    public function loadUser(int $userId)
    {
        $this->user = User::with(['roles', 'permissions'])->findOrFail($userId);
        $this->selectedRoles = $this->user->roles->pluck('id')->toArray();
        $this->selectedPermissions = $this->user->permissions->pluck('id')->toArray();
    }

    public function syncRoles()
    {
        if (!$this->user) {
            return;
        }
        try {
            $this->user->syncRoles(Role::whereIn('id', $this->selectedRoles)->pluck('name')->toArray());

            flash()->success('Rôles mis à jour avec succès.');
            $this->loadUser($this->user->id);
            $this->dispatch('user-updated');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }
    }

    public function syncPermissions()
    {
        if (!$this->user) {
            return;
        }

        try {
            $this->user->syncPermissions(Permission::whereIn('id', $this->selectedPermissions)->pluck('name')->toArray());

            session()->flash('success', 'Permissions mises à jour avec succès.');
            $this->loadUser($this->user->id);
            $this->dispatch('user-updated');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function getAvailableRolesProperty()
    {
        return Role::where('name', '!=', 'ghost')->get();
    }

    public function getAvailablePermissionsProperty()
    {
        if (!$this->user) {
            return collect();
        }

        $allPermissions = Permission::all();
        $permissionsViaRoles = $this->user->getPermissionsViaRoles()->pluck('id')->toArray();

        return $allPermissions->filter(function ($permission) use ($permissionsViaRoles) {
            return !in_array($permission->id, $permissionsViaRoles);
        });
    }

    public function getPermissionsViaRolesProperty()
    {
        if (!$this->user) {
            return collect();
        }
        //dd($this->user->getPermissionsViaRoles());
        return $this->user->getPermissionsViaRoles();
    }

    public function getDirectPermissionsProperty()
    {
        if (!$this->user) {
            return collect();
        }

        return $this->user->getDirectPermissions();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->user = null;
        $this->selectedRoles = [];
        $this->selectedPermissions = [];
    }

    public function assignRoleToUser($role)
    {
        try {
            $this->user->assignRole($role);
            flash()->success('Rôle attribué avec succès.');
            $this->dispatch('user-updated');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }
    }

    public function removeRoleFromUser($role)
    {
        try {
            $this->user->removeRole($role);
            flash()->warning('Rôle retiré avec succès.');
            $this->dispatch('user-updated');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }
    }

    public function givePermissionToUser($permission)
    {
        try {
            $this->user->givePermissionTo($permission);
            flash()->success('Permission accordée avec succès.');
            $this->dispatch('user-updated');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }
    }

    public function revokePermissionFromUser($permission)
    {
        try {
            $this->user->revokePermissionTo($permission);
            flash()->warning('Permission retirée avec succès.');
            $this->dispatch('user-updated');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }
    }
}; ?>

<flux:modal wire:model.self="showModal" name="user-permissions" class=" w-[30rem] md:w-[60rem]">
    <div class="space-y-6">
        @if ($this->user)
            <div class="space-y-4">
                {{-- Avatar et Nom --}}
                <div class="flex items-center gap-4">
                    @if ($user->hasCustomAvatar())
                        <flux:avatar name="{{ $user->name }}" src="{{ Storage::url($user->avatar_url) }}"
                            class="size-16 bg-primary bg-gradient ring-offset-2 ring-2! ring-danger!" />
                    @else
                        <flux:avatar name="{{ $user->name }}"
                            class="size-16 text-white text-4xl bg-primary bg-gradient ring-offset-2 ring-2! ring-danger!" />
                    @endif
                    <div>
                        <flux:heading size="lg">{{ $user->name }}</flux:heading>
                        <flux:text class="text-gray-500">{{ $user->username ?? $user->email }}</flux:text>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border overflow-hidden">
                {{-- Tabs Navigation --}}
                <nav class="-mb-px flex border-b bg-left" aria-label="Tabs">
                    <flux:button wire:click="$set('activeTab', 'roles')" @class([
                        'whitespace-nowrap w-full font-medium border-none! rounded-none!',
                        'text-primary! bg-primary/10!' => $activeTab === 'roles',
                        'text-gray-500 hover:text-gray-700' => $activeTab !== 'roles',
                    ])>
                        <flux:icon.shield-check variant="micro" class="inline mr-1" />
                        Rôles attribués ({{ $user->roles->count() }})
                    </flux:button>

                    <flux:button wire:click="$set('activeTab', 'permissions')" @class([
                        'whitespace-nowrap w-full font-medium border-none! rounded-none!',
                        'text-primary! bg-primary/10!' => $activeTab === 'permissions',
                        'border-transparent text-gray-500 hover:text-gray-700' =>
                            $activeTab !== 'permissions',
                    ])>
                        <flux:icon.key variant="micro" class="inline mr-1" />
                        Permissions directes ({{ $user->getDirectPermissions()->count() }})
                    </flux:button>
                </nav>

                {{-- Tab Content --}}
                <div class="px-2 py-4 space-y-6">

                    {{-- Onglet Rôles --}}
                    @if ($activeTab === 'roles')
                        <div class="space-y-4">
                            <div class="max-h-64 overflow-y-auto grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach ($this->availableRoles as $role)
                                    @if ($user->hasRole($role->name))
                                        <flux:badge
                                            class=" flex items-center gap-1 border border-success cursor-pointer"
                                            color="success" wire:click="removeRoleFromUser({{ $role->id }})">
                                            <flux:icon.shield-check variant="mini" />
                                            {{ $role->name }}
                                        </flux:badge>
                                    @else
                                        <flux:badge class="border flex items-center gap-1 cursor-pointer" color="zinc"
                                            wire:click="assignRoleToUser({{ $role->id }})">
                                            <flux:icon.shield-exclamation variant="mini" />
                                            {{ $role->name }}
                                        </flux:badge>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Onglet Permissions --}}
                    @if ($activeTab === 'permissions')
                        <div class="space-y-4">
                            {{-- Permissions via rôles (lecture seule) --}}
                            @if ($this->permissionsViaRoles->count() > 0)
                                <div class="rounded-lg bg-light p-3">
                                    <flux:text class="text-xs text-gray-500 mb-1.5">
                                        Permissions via rôles (lecture seule)
                                    </flux:text>

                                    <div class="flex flex-wrap">
                                        @foreach ($this->permissionsViaRoles as $permission)
                                            <flux:badge size="xs" color="zinc">
                                                <flux:icon.shield-check variant="micro" class="inline" />
                                                {{ $permission->name }}
                                            </flux:badge>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Permissions directes (modifiables) --}}
                            <div>
                                <flux:text class="text-xs text-gray-500 mb-3">
                                    Cliquez sur une permission pour la retirer ou l'accorder à l'utilisateur.
                                </flux:text>

                                @if ($this->availablePermissions->count() > 0)
                                    <div
                                        class="max-h-64 overflow-y-auto grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 gap-2">
                                        @foreach ($this->availablePermissions as $permission)
                                            @if ($user->hasPermissionTo($permission->name))
                                                <flux:badge
                                                    class="flex items-center gap-1 border border-success cursor-pointer"
                                                    color="success"
                                                    wire:click="revokePermissionFromUser({{ $permission->id }})">
                                                    <flux:icon.shield-check variant="mini" class="inlin mr-1" />
                                                    {{ $permission->name }}
                                                </flux:badge>
                                            @else
                                                <flux:badge class="flex items-center gap-1 border cursor-pointer"
                                                    color="zinc"
                                                    wire:click="givePermissionToUser({{ $permission->id }})">
                                                    <flux:icon.shield-exclamation variant="mini" class="inline" />
                                                    {{ $permission->name }}
                                                </flux:badge>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-8 text-gray-500">
                                        <flux:icon.shield-check class="mx-auto w-12 h-12 mb-2 text-gray-400" />
                                        <flux:text class="text-sm">
                                            Toutes les permissions sont déjà accordées via les rôles
                                        </flux:text>
                                    </div>
                                @endif
                            </div>

                        </div>
                    @endif
                </div>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="ghost">
                            Fermer
                        </flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @else
            <p>No user selected.</p>
        @endif

    </div>
</flux:modal>
