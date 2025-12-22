<?php

namespace App\Enums;

enum StorageType: string
{
    case REFRIGERATED = 'refrigerated';
    case AMBIENT = 'ambient';
    case FROZEN = 'frozen';

    public function label(): string
    {
        return match($this) {
            self::REFRIGERATED => 'Réfrigéré',
            self::AMBIENT => 'Ambiant',
            self::FROZEN => 'Congelé',
        };
    }

    public function temperature(): string
    {
        return match($this) {
            self::REFRIGERATED => '0°C à 4°C',
            self::AMBIENT => '15°C à 25°C',
            self::FROZEN => '-18°C',
        };
    }
}
