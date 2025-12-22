<?php

namespace App\Enums;

enum NotificationType: string
{
    case ORDER = 'order';
    case PRODUCTION = 'production';
    case STOCK = 'stock';
    case PAYMENT = 'payment';
    case SYSTEM = 'system';
    case MARKETING = 'marketing';

    public function label(): string
    {
        return match($this) {
            self::ORDER => 'Commande',
            self::PRODUCTION => 'Production',
            self::STOCK => 'Stock',
            self::PAYMENT => 'Paiement',
            self::SYSTEM => 'Système',
            self::MARKETING => 'Marketing',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::ORDER => 'shopping-bag',
            self::PRODUCTION => 'factory',
            self::STOCK => 'package',
            self::PAYMENT => 'credit-card',
            self::SYSTEM => 'settings',
            self::MARKETING => 'megaphone',
        };
    }
}
