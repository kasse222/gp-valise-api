<?php

namespace App\Enums;

enum BookingStatusEnum: string
{
    // 🔹 Phase initiale (expéditeur)
    case EN_ATTENTE       = 'en_attente';
    case EN_PAIEMENT      = 'en_paiement';

        // 🔹 Réponse du voyageur
    case ACCEPTE          = 'accepte';
    case REFUSE           = 'refuse';

        // 🔹 Livraison
    case CONFIRMEE        = 'confirmee';
    case LIVREE           = 'livree';
    case TERMINE          = 'termine';

        // 🔹 Exceptions & erreurs
    case ANNULE           = 'annule';
    case REMBOURSEE       = 'remboursee';
    case EXPIREE          = 'expiree';
    case EN_LITIGE        = 'en_litige';
    case PAIEMENT_ECHOUE  = 'paiement_echoue';
    case SUSPENDUE        = 'suspendue';

    // ✅ Transitions centralisées
    private const TRANSITIONS = [
        self::EN_ATTENTE       => [self::EN_PAIEMENT, self::ACCEPTE, self::REFUSE, self::ANNULE],
        self::EN_PAIEMENT      => [self::PAIEMENT_ECHOUE, self::CONFIRMEE, self::ANNULE],
        self::PAIEMENT_ECHOUE  => [self::EN_PAIEMENT, self::ANNULE],
        self::ACCEPTE          => [self::CONFIRMEE, self::ANNULE],
        self::CONFIRMEE        => [self::LIVREE, self::EN_LITIGE, self::TERMINE],
        self::LIVREE           => [self::TERMINE, self::EN_LITIGE],
        self::EN_LITIGE        => [self::REMBOURSEE],
        self::SUSPENDUE        => [self::EN_ATTENTE],
        // Les autres (finales) => aucune transition
    ];

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE      => 'En attente',
            self::EN_PAIEMENT     => 'En paiement',
            self::PAIEMENT_ECHOUE => 'Paiement échoué',
            self::ACCEPTE         => 'Acceptée',
            self::REFUSE          => 'Refusée',
            self::CONFIRMEE       => 'Confirmée',
            self::LIVREE          => 'Livrée',
            self::TERMINE         => 'Terminée',
            self::ANNULE          => 'Annulée',
            self::REMBOURSEE      => 'Remboursée',
            self::EXPIREE         => 'Expirée',
            self::EN_LITIGE       => 'En litige',
            self::SUSPENDUE       => 'Suspendue',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EN_ATTENTE      => 'gray',
            self::EN_PAIEMENT     => 'blue',
            self::PAIEMENT_ECHOUE => 'red',
            self::ACCEPTE         => 'cyan',
            self::REFUSE          => 'red',
            self::CONFIRMEE       => 'indigo',
            self::LIVREE,
            self::TERMINE         => 'green',
            self::ANNULE,
            self::REFUSE          => 'red',
            self::REMBOURSEE      => 'orange',
            self::EXPIREE         => 'gray',
            self::EN_LITIGE       => 'yellow',
            self::SUSPENDUE       => 'black',
        };
    }

    public function badge(): array
    {
        return [
            'label' => $this->label(),
            'color' => $this->color(),
        ];
    }

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

    public function isReservable(): bool
    {
        return in_array($this, [
            self::EN_ATTENTE,
            self::EN_PAIEMENT,
            self::ACCEPTE,
        ], true);
    }

    // 🎯 Transitions
    public function canTransitionTo(self $to): bool
    {
        return in_array($to, self::TRANSITIONS[$this] ?? [], true);
    }

    public function isTransitionValidFrom(self $from): bool
    {
        return $from->canTransitionTo($this);
    }

    // 🎯 Méthodes métier spécifiques
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

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function nextAllowedStatuses(): array
    {
        return self::TRANSITIONS[$this] ?? [];
    }
}
