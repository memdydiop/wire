<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case IN = 'in';
    case OUT = 'out';
    case ADJUSTMENT = 'adjustment';
    case WASTE = 'waste';
    case RETURN = 'return';

    public function label(): string
    {
        return match($this) {
            self::IN => 'Entrée',
            self::OUT => 'Sortie',
            self::ADJUSTMENT => 'Ajustement',
            self::WASTE => 'Perte',
            self::RETURN => 'Retour',
        };
    }

    public function isPositive(): bool
    {
        return in_array($this, [self::IN, self::RETURN]);
    }

    public function affectsStock(): bool
    {
        return true; // Tous les mouvements affectent le stock
    }
}
