<?php
// resources/views/livewire/admin/users/user/security-tab.blade.php

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Livewire\Volt\Component;

new class extends Component {
    public User $user;

    public function mount(User $user): void { $this->user = $user; }

    public function sendPasswordReset()
    {
        if (!auth()->user()->can('manage users')) return;

        // Logique standard Laravel Fortify/Breeze pour envoyer le lien
        $status = Password::broker()->sendResetLink(['email' => $this->user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            flash()->success('Lien de réinitialisation envoyé par email.');
        } else {
            flash()->error(__($status));
        }
    }
}; ?>

<div class="space-y-6">
    <div class="bg-white border border-zinc-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-zinc-900 mb-2">Authentification</h3>
        <p class="text-sm text-zinc-500 mb-6">Gérez les accès de cet utilisateur.</p>

        <div class="flex items-center justify-between py-4 border-t border-zinc-100">
            <div>
                <h4 class="text-sm font-medium text-zinc-900">Mot de passe</h4>
                <p class="text-xs text-zinc-500">Envoyer un email pour permettre à l'utilisateur de définir un nouveau mot de passe.</p>
            </div>
            <flux:button wire:click="sendPasswordReset" icon="envelope">Envoyer lien</flux:button>
        </div>

        <div class="flex items-center justify-between py-4 border-t border-zinc-100 opacity-50 cursor-not-allowed" title="Feature à venir">
            <div>
                <h4 class="text-sm font-medium text-zinc-900">Double authentification (2FA)</h4>
                <p class="text-xs text-zinc-500">Forcer la désactivation du 2FA.</p>
            </div>
            <flux:button size="sm" variant="ghost" disabled>Désactiver</flux:button>
        </div>
    </div>

    {{-- Zone de Danger --}}
    <div class="bg-red-50 border border-red-100 rounded-lg p-6">
        <h3 class="text-lg font-medium text-red-800 mb-2">Zone de danger</h3>
        
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-medium text-red-900">Déconnexion forcée</h4>
                    <p class="text-xs text-red-700">Invalidera toutes les sessions actives de l'utilisateur.</p>
                </div>
                <flux:button variant="danger" size="sm" class="bg-white! border-red-200! text-red-600!">Déconnecter</flux:button>
            </div>
        </div>
    </div>
</div>