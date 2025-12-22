<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case IN_PRODUCTION = 'in_production';
    case READY = 'ready';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::CONFIRMED => 'Confirmée',
            self::IN_PRODUCTION => 'En production',
            self::READY => 'Prête',
            self::COMPLETED => 'Terminée',
            self::CANCELLED => 'Annulée',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'orange',
            self::CONFIRMED => 'blue',
            self::IN_PRODUCTION => 'purple',
            self::READY => 'green',
            self::COMPLETED => 'gray',
            self::CANCELLED => 'red',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [self::CONFIRMED, self::CANCELLED]),
            self::CONFIRMED => in_array($newStatus, [self::IN_PRODUCTION, self::CANCELLED]),
            self::IN_PRODUCTION => in_array($newStatus, [self::READY, self::CANCELLED]),
            self::READY => in_array($newStatus, [self::COMPLETED]),
            self::COMPLETED, self::CANCELLED => false,
        };
    }
}