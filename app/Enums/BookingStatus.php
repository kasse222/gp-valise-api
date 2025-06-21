<?php

namespace App\Enums;

enum BookingStatusEnum: string
{
    // Phase initiale
    case EN_ATTENTE       = 'en_attente';       // par l’expéditeur
    case EN_PAIEMENT      = 'en_paiement';      // paiement en attente

        // Réponses du voyageur
    case ACCEPTEE         = 'acceptee';         // validée par le voyageur
    case REFUSEE          = 'refusee';          // refusée par le voyageur

        // État de traitement
    case CONFIRMEE        = 'confirmee';        // confirmée par la plateforme
    case LIVREE           = 'livree';           // bien livré
    case TERMINEE         = 'terminee';         // boucle fermée

        // États de sortie
    case ANNULEE          = 'annulee';          // annulée par l’utilisateur ou système
    case EXPIREE          = 'expiree';          // date dépassée sans traitement
    case REMBOURSEE       = 'remboursee';       // remboursement émis

        // État d’exception
    case EN_LITIGE        = 'en_litige';        // conflit ouvert
    case SUSPENDUE        = 'suspendue';        // manuellement suspendue
    case PAIEMENT_ECHOUE  = 'paiement_echoue';  // tentative de paiement échouée

    /**
     * Statuts terminaux (non transitoires)
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::TERMINEE,
            self::REFUSEE,
            self::ANNULEE,
            self::EXPIREE,
            self::REMBOURSEE,
        ], true);
    }

    /**
     * Statuts considérés comme "valides"
     */
    public function isValid(): bool
    {
        return in_array($this, [
            self::ACCEPTEE,
            self::CONFIRMEE,
            self::LIVREE,
            self::TERMINEE,
        ], true);
    }

    /**
     * Libellé lisible pour affichage
     */
    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE       => 'En attente',
            self::EN_PAIEMENT      => 'En paiement',
            self::ACCEPTEE         => 'Acceptée',
            self::REFUSEE          => 'Refusée',
            self::CONFIRMEE        => 'Confirmée',
            self::LIVREE           => 'Livrée',
            self::TERMINEE         => 'Terminée',
            self::ANNULEE          => 'Annulée',
            self::EXPIREE          => 'Expirée',
            self::REMBOURSEE       => 'Remboursée',
            self::EN_LITIGE        => 'En litige',
            self::SUSPENDUE        => 'Suspendue',
            self::PAIEMENT_ECHOUE  => 'Échec de paiement',
        };
    }
}
