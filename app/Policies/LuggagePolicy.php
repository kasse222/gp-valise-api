<?php

namespace App\Policies;

use App\Models\Luggage;
use App\Models\User;

class LuggagePolicy
{
    /**
     * Voir une valise (propriétaire ou admin)
     */
    public function view(User $user, Luggage $luggage): bool
    {
        return $user->id === $luggage->user_id || $user->isAdmin();
    }

    /**
     * Modifier une valise (si propriétaire)
     */
    public function update(User $user, Luggage $luggage): bool
    {
        return $user->id === $luggage->user_id;
    }

    /**
     * Supprimer une valise (si propriétaire et pas encore confirmée)
     */
    public function delete(User $user, Luggage $luggage): bool
    {
        return $user->id === $luggage->user_id;
        // Ex : && $luggage->status === LuggageStatusEnum::EN_ATTENTE;
    }

    /**
     * Annuler une valise (ex: annulation de réservation)
     */
    public function cancel(User $user, Luggage $luggage): bool
    {
        return $user->id === $luggage->user_id;
    }

    /**
     * Charger une valise (autorisé uniquement pour le voyageur)
     */
    public function load(User $user, Luggage $luggage): bool
    {
        return $luggage->booking && $user->id === $luggage->booking->trip->user_id;
    }

    /**
     * Shortcut automatique si admin
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
