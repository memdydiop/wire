<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Invitation $invitation
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Générer l'URL d'inscription avec le token
        $url = route('register.invitee', ['token' => $this->invitation->token]);

        $expiresAt = $this->invitation->expires_at->locale('fr')->isoFormat('D MMMM YYYY [à] HH:mm');
        $senderName = $this->invitation->sender?->name ?? 'L\'équipe';

        return (new MailMessage)
            ->subject('Invitation à rejoindre ' . config('app.name'))
            ->greeting('Bonjour !')
            ->line("{$senderName} vous invite à rejoindre **" . config('app.name') . "**.")
            ->line("Vous avez été invité(e) avec le rôle : **" . ucfirst($this->invitation->role) . "**")
            ->action('Créer mon compte', $url)
            ->line("Cette invitation est valable jusqu'au **{$expiresAt}**.")
            ->line("Si vous n'avez pas demandé cette invitation, vous pouvez ignorer cet email.")
            ->salutation('Cordialement, L\'équipe ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'email' => $this->invitation->email,
            'role' => $this->invitation->role,
            'expires_at' => $this->invitation->expires_at,
        ];
    }
}