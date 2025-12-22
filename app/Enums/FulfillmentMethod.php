<?php

// app/Enums/FulfillmentMethod.php
namespace App\Enums;

enum FulfillmentMethod: string
{
    case PICKUP = 'pickup';
    case DELIVERY = 'delivery';
    case IN_STORE = 'in_store';

    public function label(): string
    {
        return match($this) {
            self::PICKUP => 'À emporter',
            self::DELIVERY => 'Livraison',
            self::IN_STORE => 'Sur place',
        };
    }

    public function requiresAddress(): bool
    {
        return $this === self::DELIVERY;
    }
}