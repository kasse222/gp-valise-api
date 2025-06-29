<?php

namespace App\Enums;

enum BookingStatusEnum: int
{
    case EN_ATTENTE       = 0;
    case EN_PAIEMENT      = 1;
    case PAIEMENT_ECHOUE  = 2;
    case ACCEPTE          = 3;
    case REFUSE           = 4;
    case CONFIRMEE        = 5;
    case LIVREE           = 6;
    case TERMINE          = 7;
    case ANNULE           = 8;
    case REMBOURSEE       = 9;
    case EXPIREE          = 10;
    case EN_LITIGE        = 11;
    case SUSPENDUE        = 12;

    private const TRANSITIONS = [
        self::EN_ATTENTE->value       => [self::EN_PAIEMENT->value, self::ACCEPTE->value, self::REFUSE->value, self::ANNULE->value, self::CONFIRMEE->value],
        self::EN_PAIEMENT->value      => [self::PAIEMENT_ECHOUE->value, self::CONFIRMEE->value, self::ANNULE->value],
        self::PAIEMENT_ECHOUE->value  => [self::EN_PAIEMENT->value, self::ANNULE->value],
        self::ACCEPTE->value          => [self::CONFIRMEE->value, self::ANNULE->value],
        self::CONFIRMEE->value        => [self::LIVREE->value, self::EN_LITIGE->value, self::TERMINE->value],
        self::EN_LITIGE->value        => [self::TERMINE->value, self::ANNULE->value],
        // etc.
    ];


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
            self::EN_ATTENTE      => 'gray',
            self::EN_PAIEMENT     => 'blue',
            self::PAIEMENT_ECHOUE => 'red',
            self::ACCEPTE         => 'cyan',
            self::REFUSE,
            self::ANNULE          => 'red',
            self::CONFIRMEE       => 'indigo',
            self::LIVREE,
            self::TERMINE         => 'green',
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

    // 🔐 Logique métier
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
        return in_array($to->value, self::TRANSITIONS[$this->value] ?? [], true);
    }

    public function isTransitionValidFrom(self $from): bool
    {
        return $from->canTransitionTo($this);
    }

    public function nextAllowedStatuses(): array
    {
        return self::TRANSITIONS[$this->value] ?? [];
    }

    // 🛠️ Utilitaires
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    // 👁 Pour usage explicite dans les scopes ou tests
    public static function confirmed(): string
    {
        return self::CONFIRMEE->value;
    }

    public static function reservableStatuses(): array
    {
        return [
            self::EN_ATTENTE->value,
            self::EN_PAIEMENT->value,
            self::ACCEPTE->value,
        ];
    }
}
