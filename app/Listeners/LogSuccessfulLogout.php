<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout; // Notez l'événement Logout

class LogSuccessfulLogout
{
    public function handle(Logout $event): void
    {
        // Attention : $event->user peut être null si la session a expiré avant
        if ($event->user) {
            activity()
                ->causedBy($event->user)
                ->event('logout')
                ->log('Déconnexion du compte');
        }
    }
}