<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Attributes\{Url, On, Computed};
use App\Traits\WithDataTable;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithDataTable;

    public $bulkActionInProgress = false;

    #[On('role-updated')]
    public function refresh()
    {
        $this->resetPage();
        $this->clearSelection();
        unset($this->roles);
    }

    /**
     * Supprimer un rôle
     */
    public function delete($roleId)
    {
        try {
            $role = Role::findOrFail($roleId);

            // Protéger les rôles système
            if (in_array($role->name, ['ghost', 'admin'])) {
                flash()->error('Les rôles système ne peuvent pas être supprimés.');
                return;
            }

            // Vérifier si des utilisateurs ont ce rôle
            $usersCount = $role->users()->count();
            if ($usersCount > 0) {
                flash()->error("Impossible de supprimer ce rôle : {$usersCount} utilisateur(s) l'utilisent encore.");
                return;
            }

            $roleName = $role->name;
            $role->delete();

            flash()->success("Rôle '{$roleName}' supprimé.");
            $this->clearSelection();
            $this->resetPage();
        } catch (\Exception $e) {
            flash()->error('Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    /**
     * Suppression en masse
     */
    public function bulkDelete()
    {
        if (empty($this->selected)) {
            flash()->warning('Aucun rôle sélectionné.');
            return;
        }

        $this->bulkActionInProgress = true;

        try {
            DB::beginTransaction();

            $roles = Role::whereIn('id', $this->selected)->get();
            
            // Filtrer les rôles qui peuvent être supprimés
            $deletableRoles = $roles->filter(function($role) {
                return !in_array($role->name, ['ghost', 'admin']) 
                    && $role->users()->count() === 0;
            });

            if ($deletableRoles->isEmpty()) {
                flash()->error('Aucun rôle sélectionné ne peut être supprimé (rôles système ou utilisés).');
                DB::rollBack();
                return;
            }

            $count = $deletableRoles->count();
            Role::whereIn('id', $deletableRoles->pluck('id'))->delete();

            DB::commit();
            $this->clearSelection();
            $this->resetPage();

            flash()->success("{$count} rôle(s) supprimé(s).");
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error('Erreur lors de la suppression en masse : ' . $e->getMessage());
        } finally {
            $this->bulkActionInProgress = false;
        }
    }

    /**
     * Query logic
     */
    public function getQuery()
    {
        return Role::query()
            ->withCount(['users', 'permissions'])
            ->when($this->search, function($q) {
                $q->where('name', 'ilike', "%{$this->search}%")
                  ->orWhere('description', 'ilike', "%{$this->search}%");
            })
            ->where('name', '!=', 'ghost')
            ->orderBy($this->sortBy, $this->sortDirection);
    }

    #[Computed]
    public function roles()
    {
        return $this->getQuery()->paginate($this->perPage);
    }
}; ?>

<x-layouts.app.content :title="__('Rôles')" :heading="__('Gestion des Rôles et Permissions')">

    <x-slot:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home"/>
        <flux:breadcrumbs.item :href="route('admin.users.index')" icon="users"/>
        <flux:breadcrumbs.item icon="shield-check"/>
    </x-slot:breadcrumbs>

    <div class="card flex h-full w-full flex-1 flex-col gap-4">

        <x-data-table.layout heading="Liste des Rôles">

            <x-slot:actions>
                <flux:modal.trigger name="create-role">
                    <flux:button square icon="plus" variant="primary" />
                </flux:modal.trigger>
            </x-slot:actions>

            <x-slot:bulkActions>
                <flux:button size="sm" square variant="danger" wire:click="bulkDelete" 
                    wire:confirm="Supprimer les rôles sélectionnés ?"
                    :disabled="$bulkActionInProgress">
                    <flux:icon.trash variant="micro" />
                </flux:button>
            </x-slot:bulkActions>

            <x-slot:headers>
                <x-data-table.header column="name" label="Rôle" />
                <x-data-table.header label="Description" :sortable="false" class="hidden md:table-cell" />
                <x-data-table.header label="Utilisateurs" :sortable="false" class="hidden lg:table-cell" />
                <x-data-table.header label="Permissions" :sortable="false" class="hidden lg:table-cell" />
                <x-data-table.header column="created_at" label="Créé le" class="hidden xl:table-cell" />
                <x-data-table.header :sortable="false" class="sr-only" />
            </x-slot:headers>

            <x-slot:rows>
                @forelse($this->roles as $role)
                    <tr class="hover:bg-gray-50 transition" wire:key="role-{{ $role->id }}">
                        <td class="pr-0 pl-3 py-2 w-px">
                            <flux:checkbox wire:model.live="selected" value="{{ $role->id }}" />
                        </td>

                        <td class="px-3 py-2">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <flux:text class="flex items-center gap-2" variant="strong" size="sm">
                                        {{ ucfirst($role->name) }} 
                                        @if(in_array($role->name, ['ghost', 'admin']))
                                        <flux:icon.shield-check variant="micro" class="text-danger"/>
                                        @endif
                                    </flux:text>
                                </div>
                            </div>
                        </td>

                        <td class="hidden md:table-cell px-3 py-2">
                            <flux:text size="sm" class="text-gray-600">
                                {{ $role->description ?? 'Aucune description' }}
                            </flux:text>
                        </td>

                        <td class="hidden lg:table-cell px-3 py-2">
                            <div class="flex items-center gap-2">
                                <flux:icon.users variant="mini" class="text-gray-400" />
                                <flux:text size="sm">{{ $role->users_count }}</flux:text>
                            </div>
                        </td>

                        <td class="hidden lg:table-cell px-3 py-2">
                            <div class="flex items-center gap-2">
                                <flux:icon.key variant="mini" class="text-gray-400" />
                                <flux:text size="sm">{{ $role->permissions_count }}</flux:text>
                            </div>
                        </td>

                        <td class="hidden xl:table-cell px-3 py-2 text-gray-600">
                            <flux:text size="sm">
                                {{ $role->created_at->format('d/m/Y H:i') }}
                            </flux:text>
                        </td>

                        <td class="px-3 py-2 text-right">
                            <flux:dropdown>
                                <flux:button size="sm" icon="ellipsis-vertical" variant="ghost" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil" variant="warning"
                                        wire:click="$dispatch('open-edit-role', { roleId: {{ $role->id }} })">
                                        Modifier
                                    </flux:menu.item>

                                    <flux:menu.item icon="key"  variant="warning"
                                        wire:click="$dispatch('open-role-permissions', { roleId: {{ $role->id }} })">
                                        Permissions
                                    </flux:menu.item>

                                    @if(!in_array($role->name, ['ghost', 'admin']))
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger"
                                            wire:click="delete({{ $role->id }})"
                                            wire:confirm="Supprimer le rôle '{{ $role->name }}' ?">
                                            Supprimer
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <flux:icon.shield-check class="size-12 text-gray-300" />
                                <flux:text>Aucun rôle trouvé.</flux:text>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-slot:rows>

            {{ $this->roles->links() }}

        </x-data-table.layout>
    </div>

    <livewire:admin.roles.create-role />
    <livewire:admin.roles.edit-role />
    <livewire:admin.roles.manage-permissions />

</x-layouts.app.content>