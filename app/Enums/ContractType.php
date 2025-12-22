<?php

namespace App\Enums;

enum ContractType: string
{
    case FULL_TIME = 'full_time';
    case PART_TIME = 'part_time';
    case SEASONAL = 'seasonal';
    case INTERN = 'intern';

    public function label(): string
    {
        return match($this) {
            self::FULL_TIME => 'Temps plein',
            self::PART_TIME => 'Temps partiel',
            self::SEASONAL => 'Saisonnier',
            self::INTERN => 'Stagiaire',
        };
    }

    public function defaultWeeklyHours(): int
    {
        return match($this) {
            self::FULL_TIME => 35,
            self::PART_TIME => 20,
            self::SEASONAL => 35,
            self::INTERN => 35,
        };
    }
}
