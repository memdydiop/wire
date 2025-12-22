<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';
    case READ = 'read';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::SENT => 'Envoyée',
            self::FAILED => 'Échec',
            self::READ => 'Lue',
        };
    }
}
