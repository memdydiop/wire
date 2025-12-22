<?php

namespace App\Enums;

enum PromotionType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED_AMOUNT = 'fixed_amount';
    case BUY_X_GET_Y = 'buy_x_get_y';
    case FREE_DELIVERY = 'free_delivery';

    public function label(): string
    {
        return match($this) {
            self::PERCENTAGE => 'Pourcentage',
            self::FIXED_AMOUNT => 'Montant fixe',
            self::BUY_X_GET_Y => 'Achetez X, obtenez Y',
            self::FREE_DELIVERY => 'Livraison gratuite',
        };
    }

    public function requiresValue(): bool
    {
        return in_array($this, [self::PERCENTAGE, self::FIXED_AMOUNT]);
    }
}
