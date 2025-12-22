<?php

namespace App\Enums;

enum InvoiceType: string
{
    case INVOICE = 'invoice';
    case QUOTE = 'quote';
    case CREDIT_NOTE = 'credit_note';

    public function label(): string
    {
        return match($this) {
            self::INVOICE => 'Facture',
            self::QUOTE => 'Devis',
            self::CREDIT_NOTE => 'Avoir',
        };
    }

    public function prefix(): string
    {
        return match($this) {
            self::INVOICE => 'FA',
            self::QUOTE => 'DE',
            self::CREDIT_NOTE => 'AV',
        };
    }
}
