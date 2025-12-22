<?php

namespace App\Enums;

enum LoyaltyTransactionType: string
{
    case EARNED = 'earned';
    case REDEEMED = 'redeemed';
    case EXPIRED = 'expired';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match($this) {
            self::EARNED => 'Gagné',
            self::REDEEMED => 'Utilisé',
            self::EXPIRED => 'Expiré',
            self::ADJUSTMENT => 'Ajustement',
        };
    }

    public function isPositive(): bool
    {
        return in_array($this, [self::EARNED, self::ADJUSTMENT]);
    }
}
