<?php

use Livewire\Volt\Component;
use App\Models\User;
use Livewire\Attributes\{Url, On, Computed};
use App\Traits\WithDataTable; 
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithDataTable; 

    // --- FILTRES ---
    #[Url]
    public $statusFilter = 'all';
    #[Url]
    public $roleFilter = 'all';

    public $bulkActionInProgress = false;

    #[On('user-updated')]
    public function refresh()
    {
        $this->resetPage();
        $this->clearSelection();
    }

    // --- ACTIONS UNITAIRES ---
    public function suspendUser($userId)
    {
        try {
            $user = User::findOrFail($userId);
            // Appelle la méthode ajoutée au modèle
            $user->suspend(); 
            $this->refresh();
            flash()->warning("Utilisateur {$user->name} suspendu.");
        } catch (\LogicException $e) {
            flash()->error($e->getMessage());
        } catch (\Exception $e) {
            flash()->error('Erreur : ' . $e->getMessage());
        }
    }

    public function unSuspendUser($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $user->unSuspend();
            $this->refresh();
            flash()->success("Utilisateur {$user->name} réactivé.");
        } catch (\Exception $e) {
            flash()->error('Erreur : ' . $e->getMessage());
        }
    }

    public function delete($userId)
    {
        try {
            $user = User::findOrFail($userId);

            if (!$user->canBeDeleted()) {
                flash()->error('Action impossible : cet utilisateur est protégé.');
                return;
            }

            $userName = $user->name;
            $user->delete();

            flash()->success("Utilisateur {$userName} supprimé.");
            $this->clearSelection();
        } catch (\Exception $e) {
            flash()->error('Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    // --- ACTIONS DE MASSE ---
    public function bulkSuspend()
    {
        if (empty($this->selected)) {
            flash()->warning('Aucun utilisateur sélectionné.');
            return;
        }

        $this->bulkActionInProgress = true;

        try {
            DB::beginTransaction();

            $users = User::whereIn('id', $this->selected)->get();
            $count = 0;
            $errors = [];

            foreach ($users as $user) {
                try {
                    // Vérification de sécurité via le modèle
                    if ($user->canBeSuspended()) {
                        $user->suspend();
                        $count++;
                    }
                } catch (\LogicException $e) {
                    $errors[] = $user->name;
                }
            }

            DB::commit();
            $this->refresh();

            if ($count > 0) flash()->warning("{$count} utilisateur(s) suspendu(s).");
            if (!empty($errors)) flash()->error('Certains utilisateurs protégés n\'ont pas été suspendus.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error('Erreur lors de la suspension en masse : ' . $e->getMessage());
        } finally {
            $this->bulkActionInProgress = false;
        }
    }

    public function bulkUnSuspend()
    {
        if (empty($this->selected)) return;

        $this->bulkActionInProgress = true;

        try {
            // Pas de restriction majeure sur la réactivation, un update direct est possible
            User::whereIn('id', $this->selected)->update(['suspended_at' => null]);
            
            $this->refresh();
            flash()->success("Utilisateurs sélectionnés réactivés.");
        } catch (\Exception $e) {
            flash()->error('Erreur : ' . $e->getMessage());
        } finally {
            $this->bulkActionInProgress = false;
        }
    }

    public function bulkDelete()
    {
        if (empty($this->selected)) return;

        $this->bulkActionInProgress = true;

        try {
            DB::beginTransaction();

            $users = User::whereIn('id', $this->selected)->get();
            // Filtrer pour ne supprimer que ceux qu'on a le droit de supprimer
            $deletableUsers = $users->filter(fn($u) => $u->canBeDeleted());

            if ($deletableUsers->isEmpty()) {
                flash()->error('Aucun utilisateur sélectionné ne peut être supprimé (protégés).');
                DB::rollBack();
                return;
            }

            $count = $deletableUsers->count();
            User::whereIn('id', $deletableUsers->pluck('id'))->delete();

            DB::commit();
            $this->refresh();

            flash()->success("{$count} utilisateur(s) supprimé(s).");
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error('Erreur technique : ' . $e->getMessage());
        } finally {
            $this->bulkActionInProgress = false;
        }
    }

    // --- REQUÊTE ---
    public function getQuery()
    {
        return User::query()
            ->with('roles')
            ->withoutGhost()
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->statusFilter !== 'all', fn($q) => $q->status($this->statusFilter))
            ->when($this->roleFilter !== 'all', function ($q) {
                $q->whereHas('roles', fn($rq) => $rq->where('name', $this->roleFilter));
            })
            ->orderBy($this->sortBy, $this->sortDirection);
    }

    #[Computed]
    public function users()
    {
        return $this->getQuery()->paginate($this->perPage);
    }

    // Hooks Lifecycle
    public function updatingStatusFilter() { $this->resetPage(); $this->clearSelection(); }
    public function updatingRoleFilter() { $this->resetPage(); $this->clearSelection(); }
}; ?>


<x-layouts.app.content :title="__('Utilisateurs')" :heading="__('Gestion des Utilisateurs')">

    <x-slot:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home"/>
        <flux:breadcrumbs.item icon="users"/>
    </x-slot:breadcrumbs>

    <div class="card flex h-full w-full flex-1 flex-col gap-4">

        <x-data-table.layout heading="Liste des utilisateurs">

            <x-slot:filters>
                <flux:select wire:model.live="statusFilter" class="w-36!" placeholder="Statut">
                    <flux:select.option value="all">Tous statuts</flux:select.option>
                    <flux:select.option value="active">Actifs</flux:select.option>
                    <flux:select.option value="suspended">Suspendus</flux:select.option>
                </flux:select>
                <flux:menu.separator />
                <flux:select wire:model.live="roleFilter" class="w-36!">
                    <flux:select.option value="all">Tous rôles</flux:select.option>
                    @foreach (Role::pluck('name') as $role)
                        <flux:select.option value="{{ $role }}">{{ ucfirst($role) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </x-slot:filters>

            <x-slot:bulkActions>
                <flux:button size="sm" square variant="success" wire:click="bulkUnSuspend">
                    <flux:icon.lock-open variant="micro" />
                </flux:button>
                <flux:button size="sm" square variant="warning" wire:click="bulkSuspend">
                    <flux:icon.lock-closed variant="micro" />
                </flux:button>
                <flux:button size="sm" square variant="danger" wire:click="bulkDelete" wire:confirm="Êtes-vous sûr de vouloir supprimer ces utilisateurs ?">
                    <flux:icon.trash variant="micro" />
                </flux:button>
            </x-slot:bulkActions>

            <x-slot:headers>
                <x-data-table.header column="name" label="Utilisateur" />
                <x-data-table.header label="Rôles" :sortable="false" class="hidden md:table-cell" />
                <x-data-table.header column="created_at" label="Date création" class="hidden md:table-cell" />
                <x-data-table.header :sortable="false" class="sr-only" /> 
            </x-slot:headers>

            <x-slot:rows>
                @forelse($this->users as $user)
                    <tr class="hover:bg-gray-50 transition" wire:key="{{ $user->id }}">
                        <td class="pr-0 pl-3 py-2 w-px">
                            <flux:checkbox wire:model.live="selected" value="{{ $user->id }}" />
                        </td>

                        <td class="px-3 py-2">
                            <div class="flex items-center gap-1.5">
                                {{-- Utilisation de la méthode centralisée dans le modèle --}}
                                <flux:avatar class="font-semibold" :src="$user->avatar()" size="sm" />
                                
                                <div class="">
                                    <flux:text class="leading-4" variant="strong" size="sm">{{ $user->name }}</flux:text>
                                    <flux:text class="leading-4">{{ $user->email }}</flux:text>
                                </div>
                                @if ($user->isSuspended())
                                    <flux:icon.no-symbol variant="micro" class="text-danger" />
                                @endif
                            </div>
                        </td>

                        <td class="hidden md:table-cell px-3 py-2">
                            @foreach ($user->roles as $role)
                                <flux:badge size="xs">{{ $role->name }}</flux:badge>
                            @endforeach
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-gray-600">
                            <flux:text>{{ $user->created_at->format('d/m/Y H:i') }}</flux:text>
                        </td>

                        <td class="px-3 py-2 text-right">
                            <flux:dropdown>
                                <flux:button size="sm" icon="ellipsis-vertical" variant="ghost" />
                                <flux:menu>
                                    {{-- User Détails --}}
                                    <flux:menu.item variant="info" icon="eye"
                                        wire:click="$dispatch('open-user-details', { userId: {{ $user->id }} })">
                                        Détails
                                    </flux:menu.item>

                                    {{-- User permissions --}}
                                    <flux:menu.item variant="warning" icon="shield-check"
                                        wire:click="$dispatch('open-user-permissions', { userId: {{ $user->id }} })">
                                        Permissions
                                    </flux:menu.item>

                                    @if ($user->canBeModified())
                                        <flux:menu.separator />

                                        @if ($user->isSuspended())
                                            <flux:menu.item variant="success" icon="lock-open"
                                                wire:click="unSuspendUser({{ $user->id }})">
                                                Réactiver
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.item variant="warning" icon="lock-closed"
                                                wire:click="suspendUser({{ $user->id }})">
                                                Suspendre
                                            </flux:menu.item>
                                        @endif

                                        @if ($user->canBeDeleted())
                                            <flux:menu.item icon="trash" variant="danger"
                                                wire:click="delete({{ $user->id }})"
                                                wire:confirm="Voulez-vous vraiment supprimer cet utilisateur ?">
                                                Supprimer
                                            </flux:menu.item>
                                        @endif
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                            Aucun résultat trouvé.
                        </td>
                    </tr>
                @endforelse
            </x-slot:rows>

            {{ $this->users->links() }}

        </x-data-table.layout>
    </div>
    <livewire:admin.users.user-details />
    <livewire:admin.users.permissions />

</x-layouts.app.content>