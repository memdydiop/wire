<?php

namespace App\Enums;

enum Unit: string
{
    case KILOGRAM = 'kg';
    case GRAM = 'g';
    case LITER = 'L';
    case MILLILITER = 'ml';
    case UNIT = 'unit';
    case DOZEN = 'dozen';

    public function label(): string
    {
        return match($this) {
            self::KILOGRAM => 'Kilogramme',
            self::GRAM => 'Gramme',
            self::LITER => 'Litre',
            self::MILLILITER => 'Millilitre',
            self::UNIT => 'Unité',
            self::DOZEN => 'Douzaine',
        };
    }

    public function shortLabel(): string
    {
        return $this->value;
    }

    public function convertTo(Unit $targetUnit, float $quantity): float
    {
        // Conversion entre unités
        return match([$this, $targetUnit]) {
            [self::KILOGRAM, self::GRAM] => $quantity * 1000,
            [self::GRAM, self::KILOGRAM] => $quantity / 1000,
            [self::LITER, self::MILLILITER] => $quantity * 1000,
            [self::MILLILITER, self::LITER] => $quantity / 1000,
            default => $quantity, // Même unité ou incompatible
        };
    }
}
