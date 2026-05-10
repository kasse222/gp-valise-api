<?php

declare(strict_types=1);

namespace App\Enums;

enum DisputeDecisionEnum: string
{
    case REFUND = 'refund';
    case PAYOUT = 'payout';

    public function label(): string
    {
        return match ($this) {
            self::REFUND => 'Remboursement expéditeur',
            self::PAYOUT => 'Paiement voyageur',
        };
    }
}
