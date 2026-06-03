<?php

namespace App\Enums;

enum BookingStatusEnum: string
{
    case EN_ATTENTE           = 'en_attente';
    case PENDING_APPROVAL     = 'pending_approval';
    case EN_PAIEMENT          = 'en_paiement';
    case PAIEMENT_ECHOUE      = 'paiement_echoue';
    case CONFIRMEE            = 'confirmee';
    case LIVREE               = 'livree';
    case TERMINE              = 'termine';
    case ANNULE               = 'annule';
    case REMBOURSEE           = 'remboursee';
    case EXPIREE              = 'expiree';
    case EN_LITIGE            = 'en_litige';
    case SUSPENDUE            = 'suspendue';
    case DECLINED_BY_TRAVELER = 'declined_by_traveler';

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::EN_ATTENTE => [
                self::PENDING_APPROVAL,
                self::ANNULE,
            ],

            self::PENDING_APPROVAL => [
                self::EN_PAIEMENT,
                self::DECLINED_BY_TRAVELER,
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
                self::LIVREE,
                self::ANNULE,
                self::EN_LITIGE,
                self::REMBOURSEE,
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

    public function isPendingApproval(): bool
    {
        return $this === self::PENDING_APPROVAL;
    }

    public function isConfirmed(): bool
    {
        return $this === self::CONFIRMEE;
    }

    public function isDelivered(): bool
    {
        return $this === self::LIVREE;
    }

    public function canBeConfirmed(): bool
    {
        return $this === self::EN_PAIEMENT;
    }

    public function canBeDelivered(): bool
    {
        return $this === self::CONFIRMEE;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::EN_ATTENTE,
            self::PENDING_APPROVAL,
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
            self::LIVREE,
        ], true);
    }

    public function canBeRefunded(): bool
    {
        return $this === self::EN_LITIGE;
    }

    public function canBeApprovedByTraveler(): bool
    {
        return $this === self::PENDING_APPROVAL;
    }

    public function canBeDeclinedByTraveler(): bool
    {
        return $this === self::PENDING_APPROVAL;
    }

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE           => 'En attente',
            self::PENDING_APPROVAL     => 'En attente d\'approbation',
            self::EN_PAIEMENT          => 'En paiement',
            self::PAIEMENT_ECHOUE      => 'Paiement échoué',
            self::CONFIRMEE            => 'Confirmée',
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
