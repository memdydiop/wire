<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class LogSuccessfulLogin
{
    // Injection de la Request pour récupérer IP et User Agent
    public function __construct(public Request $request)
    {
    }

    public function handle(Login $event): void
    {
        activity()
            ->causedBy($event->user) // L'utilisateur qui vient de se connecter
            ->performedOn($event->user) // L'objet concerné (lui-même)
            ->event('login') // Le nom technique de l'événement
            ->withProperties([
                'ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
            ])
            ->log('Connexion au compte'); // La description humaine
    }
}