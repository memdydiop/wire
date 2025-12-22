<?php

use App\Models\Invitation;
use Livewire\Volt\Component;
use App\Traits\WithDataTable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\{Url, On, Computed};
use App\Notifications\InvitationNotification;

new class extends Component {
    use WithDataTable;

    #[Url]
    public $statusFilter = 'all';

    #[Url]
    public $roleFilter = 'all';

    public $bulkActionInProgress = false;

    // --- Helper pour l'affichage (Nettoie le Blade) ---
    public function getStatusBadge(Invitation $invitation): array
    {
        if ($invitation->isAccepted()) {
            return [
                'color' => 'green', 
                'label' => 'Acceptée', 
                'date_label' => 'Acceptée le', 
                'date' => $invitation->accepted_at
            ];
        }
        if ($invitation->isExpired()) {
            return [
                'color' => 'red', 
                'label' => 'Expirée', 
                'date_label' => 'Expirée le', 
                'date' => $invitation->expires_at
            ];
        }
        return [
            'color' => 'yellow', 
            'label' => 'En attente', 
            'date_label' => 'Expire le', 
            'date' => $invitation->expires_at
        ];
    }

    // --- Actions Individuelles ---

    public function resendInvitation($invitationId)
    {
        try {
            $invitation = Invitation::findOrFail($invitationId);
            
            if ($invitation->isAccepted()) {
                flash()->error('Déjà acceptée.');
                return;
            }

            $invitation->resend(7);
            
            // Notification via le trait Notifiable
            $invitation->notify(new InvitationNotification($invitation));

            flash()->success('Invitation renvoyée.');
        } catch (\Exception $e) {
            flash()->error('Erreur : ' . $e->getMessage());
        }
    }

    public function revoqueInvitation($invitationId)
    {
        try {
            $invitation = Invitation::findOrFail($invitationId);
            $invitation->revoke();
            flash()->success('Invitation révoquée.');
        } catch (\Exception $e) {
            flash()->error('Erreur : ' . $e->getMessage());
        }
    }

    public function delete($invitationId)
    {
        try {
            $invitation = Invitation::findOrFail($invitationId);
            $invitation->delete();
            flash()->success('Invitation supprimée.');
            $this->clearSelection();
        } catch (\Exception $e) {
            flash()->error('Erreur : ' . $e->getMessage());
        }
    }

    // --- Actions en masse (Refactorisées pour être DRY) ---

    protected function executeBulkAction(callable $action)
    {
        if (empty($this->selected)) {
            flash()->warning('Aucune sélection.');
            return;
        }

        $this->bulkActionInProgress = true;

        try {
            DB::beginTransaction();
            $action($this->selected);
            DB::commit();
            
            $this->clearSelection();
            $this->resetPage();
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error('Erreur technique : ' . $e->getMessage());
        } finally {
            $this->bulkActionInProgress = false;
        }
    }

    public function bulkDelete()
    {
        $this->executeBulkAction(function ($ids) {
            $count = Invitation::whereIn('id', $ids)->whereNull('accepted_at')->delete();
            flash()->success("{$count} invitation(s) supprimée(s).");
        });
    }

    public function bulkRevoke()
    {
        $this->executeBulkAction(function ($ids) {
            $count = 0;
            $invitations = Invitation::whereIn('id', $ids)->whereNull('accepted_at')->get();
            foreach ($invitations as $inv) {
                $inv->revoke();
                $count++;
            }
            flash()->success("{$count} invitation(s) révoquée(s).");
        });
    }

    public function bulkResend()
    {
        $this->executeBulkAction(function ($ids) {
            $invitations = Invitation::whereIn('id', $ids)->whereNull('accepted_at')->get();
            $count = 0;
            foreach ($invitations as $inv) {
                $inv->resend(7);
                $inv->notify(new InvitationNotification($inv));
                $count++;
            }
            flash()->success("{$count} invitation(s) renvoyée(s).");
        });
    }

    // --- Query & Hooks ---

    #[On('invitation-sent')]
    public function refreshInvitations() { unset($this->invitations); }

    public function getQuery()
    {
        return Invitation::query()
            ->with('sender') // Optimisation N+1
            ->when($this->search, fn($q) => $q->where('email', 'like', "%{$this->search}%"))
            ->when($this->statusFilter !== 'all', fn($q) => match($this->statusFilter) {
                'valid' => $q->valid(),
                'expired' => $q->expired(),
                'accepted' => $q->accepted(),
                'pending' => $q->pending(),
                default => $q
            })
            ->when($this->roleFilter !== 'all', fn($q) => $q->where('role', $this->roleFilter))
            ->orderBy($this->sortBy, $this->sortDirection);
    }

    #[Computed]
    public function invitations()
    {
        return $this->getQuery()->paginate($this->perPage);
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->pluck('name');
    }

    public function updatingStatusFilter() { $this->resetPage(); $this->clearSelection(); }
    public function updatingRoleFilter() { $this->resetPage(); $this->clearSelection(); }
}; ?>

<x-layouts.app.content :title="__('Invitations')" :heading="__('Gestion des Invitations')">

    <x-slot:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" />
        <flux:breadcrumbs.item :href="route('admin.users.index')" icon="users" />
        <flux:breadcrumbs.item icon="envelope" />
    </x-slot:breadcrumbs>

    <div class="card flex h-full w-full flex-1 flex-col gap-4">

        <x-data-table.layout heading="Liste des Invitations">

            <x-slot:actions>
                <flux:modal.trigger name="create-invitation">
                    <flux:button square icon="paper-airplane" variant="primary" />
                </flux:modal.trigger>
            </x-slot:actions>

            <x-slot:filters>
                <flux:select wire:model.live="statusFilter" class="w-36!" placeholder="Statut">
                    <flux:select.option value="all">Tous statuts</flux:select.option>
                    <flux:select.option value="valid">Valides</flux:select.option>
                    <flux:select.option value="expired">Expirées</flux:select.option>
                    <flux:select.option value="accepted">Acceptées</flux:select.option>
                    <flux:select.option value="pending">En attente</flux:select.option>
                </flux:select>
                
                <flux:select wire:model.live="roleFilter" class="w-36!" placeholder="Rôles">
                    <flux:select.option value="all">Tous rôles</flux:select.option>
                    @foreach ($this->roles as $role)
                        <flux:select.option value="{{ $role }}">{{ ucfirst($role) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </x-slot:filters>

            <x-slot:bulkActions>
                <div class="flex gap-2" wire:loading.class="opacity-50 pointer-events-none">
                    <flux:button size="sm" square variant="primary" wire:click="bulkResend"
                        wire:confirm="Renvoyer la sélection ?" :disabled="$bulkActionInProgress" tooltip="Renvoyer">
                        <flux:icon.paper-airplane variant="micro" />
                    </flux:button>
                    <flux:button size="sm" square variant="warning" wire:click="bulkRevoke"
                        wire:confirm="Révoquer la sélection ?" :disabled="$bulkActionInProgress" tooltip="Révoquer">
                        <flux:icon.no-symbol variant="micro" />
                    </flux:button>
                    <flux:button size="sm" square variant="danger" wire:click="bulkDelete"
                        wire:confirm="Supprimer la sélection ?" :disabled="$bulkActionInProgress" tooltip="Supprimer">
                        <flux:icon.trash variant="micro" />
                    </flux:button>
                </div>
            </x-slot:bulkActions>

            <x-slot:headers>
                <x-data-table.header column="email" label="Email" />
                <x-data-table.header label="Envoyée par" class="hidden lg:table-cell" :sortable="false" />
                <x-data-table.header label="Statut" :sortable="false" />
                <x-data-table.header column="created_at" label="Date" class="hidden xl:table-cell" />
                <x-data-table.header :sortable="false" class="sr-only" />
            </x-slot:headers>

            <x-slot:rows>
                @forelse($this->invitations as $invitation)
                    <tr class="hover:bg-gray-50 transition border-b border-gray-100 last:border-0" wire:key="invitation-{{ $invitation->id }}">
                        
                        <td class="pr-0 pl-3 py-3 w-px">
                            <flux:checkbox wire:model.live="selected" value="{{ $invitation->id }}" />
                        </td>

                        <td class="px-3 py-2">
                            <div class="flex items-center gap-3">
                                <div>
                                    <flux:text variant="strong" class="block truncate max-w-[200px]">
                                        {{ $invitation->email }}
                                    </flux:text>
                                    <flux:badge size="xs" color="zinc">{{ ucfirst($invitation->role) }}</flux:badge>
                                </div>
                            </div>
                        </td>

                        <td class="hidden lg:table-cell px-3 py-2">
                            @if ($invitation->sender)
                                <div class="flex items-center gap-2">
                                    @if ($invitation->sender->hasCustomAvatar())
                                        <flux:avatar :src="Storage::url($invitation->sender->avatar_url)" size="sm" />
                                    @else
                                        <flux:avatar :initials="$invitation->sender->initials()" size="sm" />
                                    @endif
                                    <flux:text class="text-gray-600">{{ $invitation->sender->name }}</flux:text>
                                </div>
                            @else
                                <flux:text size="sm" class="text-gray-400 italic">Système</flux:text>
                            @endif
                        </td>

                        <td class="px-3 py-2">
                            @php $status = $this->getStatusBadge($invitation); @endphp
                            <div class="flex flex-col items-start gap-1">
                                <flux:badge size="xs" color="{{ $status['color'] }}">
                                    {{ $status['label'] }}
                                </flux:badge>
                                <flux:text size="2xs" class="text-gray-400">
                                    {{ $status['date_label'] }} {{ $status['date']->format('d/m H:i') }}
                                </flux:text>
                            </div>
                        </td>

                        <td class="hidden xl:table-cell px-3 py-2">
                            <flux:text class="">
                                {{ $invitation->created_at->format('d/m/Y') }}
                            </flux:text>
                        </td>

                        <td class="px-3 py-2 text-right">
                            @if (!$invitation->isAccepted())
                                <flux:dropdown>
                                    <flux:button size="sm" icon="ellipsis-vertical" variant="ghost" />
                                    <flux:menu>
                                        <flux:menu.item icon="paper-airplane" wire:click="resendInvitation({{ $invitation->id }})">
                                            Renvoyer
                                        </flux:menu.item>
                                        <flux:menu.item icon="no-symbol" variant="warning" wire:click="revoqueInvitation({{ $invitation->id }})">
                                            Révoquer
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $invitation->id }})" wire:confirm="Supprimer ?">
                                            Supprimer
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @else
                                <flux:icon.check-circle class="size-5 text-green-500 mx-auto" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <flux:icon.envelope class="size-10 text-gray-300" />
                                <p>Aucune invitation trouvée.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-slot:rows>

            {{ $this->invitations->links() }}

        </x-data-table.layout>
    </div>

    <livewire:admin.users.send-invitation />

</x-layouts.app.content>