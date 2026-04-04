<?php

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case CHARGE = 'charge';
    case REFUND = 'refund';
    case PAYOUT = 'payout';

    public function label(): string
    {
        return match ($this) {
            self::CHARGE => 'Encaissement',
            self::REFUND => 'Remboursement',
            self::PAYOUT => 'Versement voyageur',
        };
    }
}
