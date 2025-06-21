<?php

namespace App\Enums;

enum BookingStatusEnum: string
{
    // 🔹 Phase initiale (côté expéditeur)
    case EN_ATTENTE       = 'en_attente';
    case EN_PAIEMENT      = 'en_paiement';

        // 🔹 Réponse du voyageur
    case ACCEPTE          = 'accepte';
    case REFUSE           = 'refuse';

        // 🔹 Validation et livraison
    case CONFIRMEE        = 'confirmee';
    case LIVREE           = 'livree';
    case TERMINE          = 'termine';

        // 🔹 Annulation et exceptions
    case ANNULE           = 'annule';
    case REMBOURSEE       = 'remboursee';
    case EXPIREE          = 'expiree';
    case EN_LITIGE        = 'en_litige';
    case PAIEMENT_ECHOUE  = 'paiement_echoue';
    case SUSPENDUE        = 'suspendue';


    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE       => 'En attente',
            self::EN_PAIEMENT      => 'En paiement',
            self::PAIEMENT_ECHOUE  => 'Paiement échoué',
            self::ACCEPTE          => 'Acceptée',
            self::REFUSE           => 'Refusée',
            self::CONFIRMEE        => 'Confirmée',
            self::LIVREE           => 'Livrée',
            self::TERMINE          => 'Terminée',
            self::ANNULE           => 'Annulée',
            self::REMBOURSEE       => 'Remboursée',
            self::EXPIREE          => 'Expirée',
            self::EN_LITIGE        => 'En litige',
            self::SUSPENDUE        => 'Suspendue',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EN_ATTENTE       => 'gray',
            self::EN_PAIEMENT      => 'blue',
            self::PAIEMENT_ECHOUE  => 'red',
            self::ACCEPTE          => 'cyan',
            self::REFUSE           => 'red',
            self::CONFIRMEE        => 'indigo',
            self::LIVREE           => 'green',
            self::TERMINE          => 'green',
            self::ANNULE           => 'red',
            self::REMBOURSEE       => 'orange',
            self::EXPIREE          => 'gray',
            self::EN_LITIGE        => 'yellow',
            self::SUSPENDUE        => 'black',
        };
    }

    /**
     * Retourne true si le statut est considéré comme un état final (plus d’action possible).
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::TERMINE,
            self::REFUSE,
            self::ANNULE,
            self::REMBOURSEE,
            self::EXPIREE,
        ], true);
    }

    /**
     * Retourne true si une réservation peut encore être modifiée ou annulée.
     */
    public function isReservable(): bool
    {
        return in_array($this, [
            self::EN_ATTENTE,
            self::EN_PAIEMENT,
            self::ACCEPTE,
        ], true);
    }

    // 🎯 Méthodes métier

    public function canBeCancelled(): bool
    {
        return !in_array($this, [
            self::TERMINE,
            self::ANNULE,
            self::REMBOURSEE,
        ], true);
    }

    public function canBeConfirmed(): bool
    {
        return in_array($this, [
            self::EN_ATTENTE,
            self::ACCEPTE,
        ], true);
    }

    public function canBeDelivered(): bool
    {
        return in_array($this, [
            self::CONFIRMEE,
            self::LIVREE,
        ], true);
    }

    public function canBeDisputed(): bool
    {
        return in_array($this, [
            self::LIVREE,
            self::TERMINE,
        ], true);
    }

    public function canBeRefunded(): bool
    {
        return in_array($this, [
            self::ANNULE,
            self::PAIEMENT_ECHOUE,
        ], true);
    }

    public function canTransitionTo(self $to): bool
    {
        $transitions = [
            self::EN_ATTENTE => [
                self::EN_PAIEMENT,
                self::ACCEPTE,
                self::REFUSE,
                self::ANNULE,
            ],
            self::EN_PAIEMENT => [
                self::PAIEMENT_ECHOUE,
                self::CONFIRMEE,
                self::ANNULE,
            ],
            self::PAIEMENT_ECHOUE => [
                self::EN_PAIEMENT,
                self::ANNULE,
            ],
            self::ACCEPTE => [
                self::CONFIRMEE,
                self::ANNULE,
            ],
            self::REFUSE => [],
            self::CONFIRMEE => [
                self::LIVREE,
                self::EN_LITIGE,
                self::TERMINE,
            ],
            self::LIVREE => [
                self::TERMINE,
                self::EN_LITIGE,
            ],
            self::TERMINE => [],
            self::EN_LITIGE => [
                self::REMBOURSEE,
            ],
            self::SUSPENDUE => [
                self::EN_ATTENTE,
            ],
            self::REMBOURSEE => [],
            self::ANNULE => [],
            self::EXPIREE => [],
        ];

        return in_array($to, $transitions[$this] ?? [], true);
    }


    /**
     * Retourne tous les statuts possibles (utile pour des validations).
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
