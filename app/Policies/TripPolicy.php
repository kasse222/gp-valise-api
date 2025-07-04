<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    public function update(User $user, Trip $trip): bool
    {
        return $trip->user_id === $user->id || $user->isAdmin();
    }
    public function delete(User $user, Trip $trip): bool
    {
        return $user->id === $trip->user_id || $user->isAdmin();
    }


    public function manage(User $user, Trip $trip): bool
    {
        return $user->id === $trip->user_id;
    }

    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
