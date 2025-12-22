<?php

namespace App\Enums;

enum AlertStatus: string
{
    case ACTIVE = 'active';
    case ACKNOWLEDGED = 'acknowledged';
    case RESOLVED = 'resolved';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::ACKNOWLEDGED => 'Prise en compte',
            self::RESOLVED => 'Résolue',
        };
    }
}
