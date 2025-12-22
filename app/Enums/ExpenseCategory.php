<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case INGREDIENTS = 'ingredients';
    case EQUIPMENT = 'equipment';
    case UTILITIES = 'utilities';
    case RENT = 'rent';
    case SALARIES = 'salaries';
    case MARKETING = 'marketing';
    case MAINTENANCE = 'maintenance';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::INGREDIENTS => 'Ingrédients',
            self::EQUIPMENT => 'Équipement',
            self::UTILITIES => 'Services publics',
            self::RENT => 'Loyer',
            self::SALARIES => 'Salaires',
            self::MARKETING => 'Marketing',
            self::MAINTENANCE => 'Maintenance',
            self::OTHER => 'Autre',
        };
    }

    public function isRecurring(): bool
    {
        return in_array($this, [self::RENT, self::SALARIES, self::UTILITIES]);
    }
}
