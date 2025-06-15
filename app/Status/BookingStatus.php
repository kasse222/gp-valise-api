<?php

namespace App\Status;

enum BookingStatus: string
{
    // Phase initiale
    case EN_ATTENTE       = 'en_attente';       // par l’expéditeur
    case EN_PAIEMENT      = 'en_paiement';      // paiement non encore validé

        // Réponses du voyageur
    case ACCEPTE          = 'accepte';
    case REFUSE           = 'refuse';

        // Cas post-acceptation
    case TERMINE          = 'termine';          // livraison effectuée
    case ANNULE           = 'annule';           // par expéditeur ou système

        // Exceptions
    case EN_LITIGE        = 'en_litige';        // conflit / réclamation en cours
    case REMBOURSEE       = 'remboursee';       // après annulation
    case EXPIREE          = 'expiree';          // date dépassée sans action
    case PAIEMENT_ECHOUE  = 'paiement_echoue';  // tentative échouée
    case SUSPENDUE        = 'suspendue';        // manuellement désactivée

    public function isFinal(): bool
    {
        return in_array($this, [
            self::TERMINE,
            self::REFUSE,
            self::ANNULE,
            self::REMBOURSEE,
            self::EXPIREE,
        ]);
    }
}
