<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case PUSH = 'push';
    case IN_APP = 'in_app';

    public function label(): string
    {
        return match($this) {
            self::EMAIL => 'Email',
            self::SMS => 'SMS',
            self::PUSH => 'Notification push',
            self::IN_APP => 'Application',
        };
    }
    
}
