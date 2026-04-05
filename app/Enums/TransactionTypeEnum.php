<?php

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case CHARGE = 'charge';
    case REFUND = 'refund';
    case FEE    = 'fee';
    case PAYOUT = 'payout';


    public function label(): string
    {
        return match ($this) {
            self::CHARGE => 'Encaissement',
            self::REFUND => 'Remboursement',
            self::FEE    => 'Commission',
            self::PAYOUT => 'Versement voyageur',
        };
    }
}
