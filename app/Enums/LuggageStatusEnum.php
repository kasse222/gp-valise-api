<?php

namespace App\Enums;

enum LuggageStatus: string
{
    // 🔹 Cycle standard
    case EN_ATTENTE  = 'en_attente';     // Créée, non affectée
    case RESERVEE    = 'reservee';       // Affectée à un trajet
    case EN_TRANSIT  = 'en_transit';     // En cours d’acheminement
    case LIVREE      = 'livree';         // Livrée au destinataire

        // 🔹 Anomalies ou cas terminaux
    case ANNULEE     = 'annulee';        // Annulée avant transport
    case PERDUE      = 'perdue';         // Perte ou litige grave
    case RETOUR      = 'retour';         // Renvoyée à l’expéditeur

    // === Méthodes pour affichage ===

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::RESERVEE   => 'Réservée',
            self::EN_TRANSIT => 'En transit',
            self::LIVREE     => 'Livrée',
            self::ANNULEE    => 'Annulée',
            self::PERDUE     => 'Perdue',
            self::RETOUR     => 'En retour',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'gray',
            self::RESERVEE   => 'blue',
            self::EN_TRANSIT => 'indigo',
            self::LIVREE     => 'green',
            self::ANNULEE    => 'red',
            self::PERDUE     => 'orange',
            self::RETOUR     => 'purple',
        };
    }

    // === Méthodes métier ===

    public function isReservable(): bool
    {
        return $this === self::EN_ATTENTE;
    }

    public function isTransportable(): bool
    {
        return in_array($this, [
            self::RESERVEE,
            self::EN_TRANSIT,
        ]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::LIVREE,
            self::ANNULEE,
            self::PERDUE,
            self::RETOUR,
        ], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::EN_ATTENTE,
            self::RESERVEE,
        ]);
    }

    public function canBeDelivered(): bool
    {
        return $this === self::EN_TRANSIT;
    }

    public function canBeDisputed(): bool
    {
        return $this === self::LIVREE;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
