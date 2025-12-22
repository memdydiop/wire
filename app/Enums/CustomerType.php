<?php

namespace App\Enums;

enum CustomerType: string
{
    case REGULAR = 'regular';
    case VIP = 'vip';
    case PROFESSIONAL = 'professional';

    public function label(): string
    {
        return match($this) {
            self::REGULAR => 'Régulier',
            self::VIP => 'VIP',
            self::PROFESSIONAL => 'Professionnel',
        };
    }

    public function loyaltyMultiplier(): float
    {
        return match($this) {
            self::REGULAR => 1.0,
            self::VIP => 1.5,
            self::PROFESSIONAL => 1.2,
        };
    }

    public function discountPercentage(): float
    {
        return match($this) {
            self::REGULAR => 0,
            self::VIP => 5,
            self::PROFESSIONAL => 10,
        };
    }
}
