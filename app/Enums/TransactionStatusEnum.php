<?php

namespace App\Enums;

enum TransactionStatusEnum: string
{
    case PENDING     = 'pending';
    case PROCESSING  = 'processing';
    case COMPLETED   = 'completed';
    case FAILED      = 'failed';
    case REFUNDED    = 'refunded';
    case CANCELLED   = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING     => 'En attente',
            self::PROCESSING  => 'En traitement',
            self::COMPLETED   => 'Complétée',
            self::FAILED      => 'Échouée',
            self::REFUNDED    => 'Remboursée',
            self::CANCELLED   => 'Annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING     => 'gray',
            self::PROCESSING  => 'blue',
            self::COMPLETED   => 'green',
            self::FAILED      => 'red',
            self::REFUNDED    => 'orange',
            self::CANCELLED   => 'red',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::REFUNDED,
            self::CANCELLED,
        ], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function badge(): array
    {
        return [
            'label' => $this->label(),
            'color' => $this->color(),
        ];
    }

    public function canTransitionTo(self $to): bool
    {
        $transitions = [
            self::PENDING => [
                self::PROCESSING,
                self::CANCELLED,
            ],
            self::PROCESSING => [
                self::COMPLETED,
                self::FAILED,
                self::REFUNDED,
            ],
            self::COMPLETED => [
                self::REFUNDED,
            ],
            self::FAILED => [],
            self::REFUNDED => [],
            self::CANCELLED => [],
        ];

        return in_array($to, $transitions[$this] ?? [], true);
    }

    /**
     * ✅ Peut-on rembourser cette transaction ?
     */
    public function canBeRefunded(): bool
    {
        return in_array($this, [
            self::PROCESSING,
            self::COMPLETED,
        ], true);
    }

    /**
     * ✅ Peut-on encore annuler cette transaction ?
     */
    public function isCancelable(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::PROCESSING,
        ], true);
    }
    public function isSuccess(): bool
    {
        return $this === self::COMPLETED || $this === self::REFUNDED;
    }
}
