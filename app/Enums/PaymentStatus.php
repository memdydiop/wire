<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::UNPAID => 'Non payé',
            self::PARTIAL => 'Partiellement payé',
            self::PAID => 'Payé',
            self::REFUNDED => 'Remboursé',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::PAID;
    }
}