<?php

namespace App\Enums;

enum TripStatusEnum: string
{
    case PENDING   = 'pending';
    case ACTIVE    = 'active';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'En attente',
            self::ACTIVE    => 'Actif',
            self::CANCELLED => 'Annulé',
            self::COMPLETED => 'Terminé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING   => 'gray',
            self::ACTIVE    => 'green',
            self::CANCELLED => 'red',
            self::COMPLETED => 'blue',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::CANCELLED, self::COMPLETED]);
    }
    public function isReservable(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::ACTIVE,
        ], true);
    }
}
