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

    /**
     * 🔄 Transitions autorisées (définies par valeurs string, pas d'objet enum !)
     */
    private const TRANSITIONS = [
        'en_attente'      => ['en_paiement', 'accepte', 'refuse', 'annule'],
        'en_paiement'     => ['paiement_echoue', 'confirmee', 'annule'],
        'paiement_echoue' => ['en_paiement', 'annule'],
        'accepte'         => ['confirmee', 'annule'],
        'confirmee'       => ['livree', 'en_litige', 'termine'],
        'livree'          => ['termine', 'en_litige'],
        'en_litige'       => ['remboursee'],
        'suspendue'       => ['en_attente'],
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

    public function canTransitionTo(self $to): bool
    {
        return in_array($to->value, self::TRANSITIONS[$this->value] ?? [], true);
    }

    public function isTransitionValidFrom(self $from): bool
    {
        return $from->canTransitionTo($this);
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

    public function nextAllowedStatuses(): array
    {
        return self::TRANSITIONS[$this->value] ?? [];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
