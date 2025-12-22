<?php

namespace App\Enums;

enum ExpenseStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::PAID => 'Payé',
            self::CANCELLED => 'Annulé',
        };
    }
}
