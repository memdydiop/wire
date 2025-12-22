<?php

// app/Enums/OrderType.php
namespace App\Enums;

enum OrderType: string
{
    case IN_STORE = 'in_store';
    case ONLINE = 'online';
    case PHONE = 'phone';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match($this) {
            self::IN_STORE => 'En boutique',
            self::ONLINE => 'En ligne',
            self::PHONE => 'Par téléphone',
            self::CUSTOM => 'Sur mesure',
        };
    }
}