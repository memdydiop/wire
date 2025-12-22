<?php

namespace App\Enums;

enum ShiftStatus: string
{
    case SCHEDULED = 'scheduled';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case ABSENT = 'absent';

    public function label(): string
    {
        return match($this) {
            self::SCHEDULED => 'Planifié',
            self::CONFIRMED => 'Confirmé',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
            self::ABSENT => 'Absent',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::SCHEDULED => 'gray',
            self::CONFIRMED => 'blue',
            self::COMPLETED => 'green',
            self::CANCELLED => 'orange',
            self::ABSENT => 'red',
        };
    }
}
