<?php

namespace App\Enums;

enum StockAlertType: string
{
    case LOW_STOCK = 'low_stock';
    case OUT_OF_STOCK = 'out_of_stock';
    case EXPIRING_SOON = 'expiring_soon';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match($this) {
            self::LOW_STOCK => 'Stock faible',
            self::OUT_OF_STOCK => 'Rupture de stock',
            self::EXPIRING_SOON => 'Expire bientôt',
            self::EXPIRED => 'Expiré',
        };
    }

    public function severity(): string
    {
        return match($this) {
            self::OUT_OF_STOCK, self::EXPIRED => 'critical',
            self::LOW_STOCK, self::EXPIRING_SOON => 'warning',
        };
    }
}
