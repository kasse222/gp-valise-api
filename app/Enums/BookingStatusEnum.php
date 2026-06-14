<?php

namespace App\Enums;

enum BookingStatusEnum: string
{
    case EN_ATTENTE       = 'en_attente';
    case EN_PAIEMENT      = 'en_paiement';
    case PAIEMENT_ECHOUE  = 'paiement_echoue';
    case CONFIRMEE        = 'confirmee';
    case EN_TRANSIT       = 'en_transit';      // ← NOUVEAU : remise physique faite
    case LIVREE           = 'livree';
    case TERMINE          = 'termine';
    case ANNULE           = 'annule';
    case REMBOURSEE       = 'remboursee';
    case EXPIREE          = 'expiree';
    case EN_LITIGE        = 'en_litige';
    case SUSPENDUE        = 'suspendue';

    // Conservés pour compatibilité données existantes — JAMAIS utilisés dans le nouveau flow
    /** @deprecated Instant Booking — ne plus créer de bookings dans ces états */
    case PENDING_APPROVAL     = 'pending_approval';
    /** @deprecated Instant Booking */
    case DECLINED_BY_TRAVELER = 'declined_by_traveler';

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            // Instant Booking : EN_ATTENTE → EN_PAIEMENT directement
            self::EN_ATTENTE => [
                self::EN_PAIEMENT,
                self::ANNULE,
            ],

            self::EN_PAIEMENT => [
                self::CONFIRMEE,
                self::PAIEMENT_ECHOUE,
                self::EXPIREE,
                self::ANNULE,
            ],

            self::PAIEMENT_ECHOUE => [
                self::EN_PAIEMENT,
                self::ANNULE,
            ],

            self::CONFIRMEE => [
                self::EN_TRANSIT,   // ← remise physique sender → traveler
                self::ANNULE,
                self::EN_LITIGE,
                self::REMBOURSEE,
            ],

            self::EN_TRANSIT => [
                self::LIVREE,       // ← scan QR / code secret destinataire
                self::EN_LITIGE,
            ],

            self::LIVREE => [
                self::TERMINE,
                self::EN_LITIGE,
            ],

            self::EN_LITIGE => [
                self::REMBOURSEE,
                self::TERMINE,
                self::SUSPENDUE,
            ],

            self::SUSPENDUE => [
                self::EN_LITIGE,
                self::ANNULE,
            ],

            // Légacy — aucune transition sortante active
            self::PENDING_APPROVAL => [
                self::EN_PAIEMENT,
                self::DECLINED_BY_TRAVELER,
                self::ANNULE,
            ],

            self::ANNULE,
            self::REMBOURSEE,
            self::EXPIREE,
            self::TERMINE,
            self::DECLINED_BY_TRAVELER => [],
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::ANNULE,
            self::REMBOURSEE,
            self::EXPIREE,
            self::TERMINE,
            self::DECLINED_BY_TRAVELER,
        ], true);
    }

    public function isPaymentPending(): bool
    {
        return $this === self::EN_PAIEMENT;
    }

    public function isConfirmed(): bool
    {
        return $this === self::CONFIRMEE;
    }

    public function isInTransit(): bool
    {
        return $this === self::EN_TRANSIT;
    }

    public function isDelivered(): bool
    {
        return $this === self::LIVREE;
    }

    public function canBeConfirmed(): bool
    {
        return $this === self::EN_PAIEMENT;
    }

    public function canBeTransited(): bool
    {
        return $this === self::CONFIRMEE;
    }

    public function canBeDelivered(): bool
    {
        return $this === self::EN_TRANSIT;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::EN_ATTENTE,
            self::EN_PAIEMENT,
            self::CONFIRMEE,
        ], true);
    }

    public function canBeExpired(): bool
    {
        return $this === self::EN_PAIEMENT;
    }

    public function canEnterDispute(): bool
    {
        return in_array($this, [
            self::CONFIRMEE,
            self::EN_TRANSIT,
            self::LIVREE,
        ], true);
    }

    public function canBeRefunded(): bool
    {
        return $this === self::EN_LITIGE;
    }

    /** @deprecated Instant Booking — toujours false dans le nouveau flow */
    public function isPendingApproval(): bool
    {
        return $this === self::PENDING_APPROVAL;
    }

    /** @deprecated Instant Booking */
    public function canBeApprovedByTraveler(): bool
    {
        return false;
    }

    /** @deprecated Instant Booking */
    public function canBeDeclinedByTraveler(): bool
    {
        return false;
    }

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE           => 'En attente',
            self::PENDING_APPROVAL     => 'En attente d\'approbation',
            self::EN_PAIEMENT          => 'En paiement',
            self::PAIEMENT_ECHOUE      => 'Paiement échoué',
            self::CONFIRMEE            => 'Confirmée',
            self::EN_TRANSIT           => 'En transit',
            self::LIVREE               => 'Livrée',
            self::TERMINE              => 'Terminée',
            self::ANNULE               => 'Annulée',
            self::REMBOURSEE           => 'Remboursée',
            self::EXPIREE              => 'Expirée',
            self::EN_LITIGE            => 'En litige',
            self::SUSPENDUE            => 'Suspendue',
            self::DECLINED_BY_TRAVELER => 'Refusée par le voyageur',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EN_ATTENTE           => 'gray',
            self::PENDING_APPROVAL     => 'yellow',
            self::EN_PAIEMENT          => 'yellow',
            self::PAIEMENT_ECHOUE      => 'red',
            self::CONFIRMEE            => 'blue',
            self::EN_TRANSIT           => 'indigo',
            self::LIVREE               => 'green',
            self::TERMINE              => 'green',
            self::ANNULE               => 'red',
            self::REMBOURSEE           => 'purple',
            self::EXPIREE              => 'orange',
            self::EN_LITIGE            => 'red',
            self::SUSPENDUE            => 'gray',
            self::DECLINED_BY_TRAVELER => 'red',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
