<?php

namespace App\Enums;

enum TripStatusEnum: string
{
    case PENDING   = 'pending';    // En attente de confirmation
    case ACTIVE    = 'active';     // Disponible à la réservation
    case CANCELLED = 'cancelled';  // Annulé par le voyageur
    case COMPLETED = 'completed';  // Voyage terminé

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
}
