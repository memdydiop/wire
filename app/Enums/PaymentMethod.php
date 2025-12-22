<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case CARD = 'card';
    case TRANSFER = 'transfer';
    case CHECK = 'check';
    case ONLINE = 'online';

    public function label(): string
    {
        return match($this) {
            self::CASH => 'Espèces',
            self::CARD => 'Carte bancaire',
            self::TRANSFER => 'Virement',
            self::CHECK => 'Chèque',
            self::ONLINE => 'Paiement en ligne',
        };
    }

    public function isImmediate(): bool
    {
        return in_array($this, [self::CASH, self::CARD, self::ONLINE]);
    }
}
