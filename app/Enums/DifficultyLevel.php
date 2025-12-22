<?php

namespace App\Enums;

enum DifficultyLevel: string
{
    case EASY = 'easy';
    case MEDIUM = 'medium';
    case HARD = 'hard';
    case EXPERT = 'expert';

    public function label(): string
    {
        return match($this) {
            self::EASY => 'Facile',
            self::MEDIUM => 'Moyen',
            self::HARD => 'Difficile',
            self::EXPERT => 'Expert',
        };
    }

    public function stars(): int
    {
        return match($this) {
            self::EASY => 1,
            self::MEDIUM => 2,
            self::HARD => 3,
            self::EXPERT => 4,
        };
    }
}
