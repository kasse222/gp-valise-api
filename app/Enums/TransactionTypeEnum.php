<?php

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case CHARGE      = 'charge';
    case REFUND      = 'refund';
    case FEE         = 'fee';
    case PAYMENT_FEE = 'payment_fee';
    case PAYOUT      = 'payout';

    public function label(): string
    {
        return match ($this) {
            self::CHARGE      => 'Encaissement',
            self::REFUND      => 'Remboursement',
            self::FEE         => 'Commission',
            self::PAYMENT_FEE => 'Frais PSP',
            self::PAYOUT      => 'Versement voyageur',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CHARGE      => 'info',
            self::REFUND      => 'warning',
            self::FEE         => 'primary',
            self::PAYMENT_FEE => 'danger',
            self::PAYOUT      => 'success',
        };
    }

    public function badge(): array
    {
        return [
            'label' => $this->label(),
            'color' => $this->color(),
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
