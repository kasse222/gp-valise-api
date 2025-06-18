<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    /**
     * Afficher le trip si l'utilisateur en est le propriétaire.
     */
    public function view(User $user, Trip $trip): bool
    {
        return $user->id === $trip->user_id;
    }

    /**
     * Modifier uniquement si propriétaire.
     */
    public function update(User $user, Trip $trip): bool
    {
        return $user->id === $trip->user_id;
    }

    /**
     * Supprimer uniquement si propriétaire (et potentiellement : pas de bookings confirmés).
     */
    public function delete(User $user, Trip $trip): bool
    {
        return $user->id === $trip->user_id;
    }
}
