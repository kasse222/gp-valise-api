<?php

namespace App\Policies;

use App\Models\Luggage;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LuggagePolicy
{
    /**
     * L’utilisateur peut voir ou modifier ses propres valises
     */
    public function view(User $user, Luggage $luggage): bool
    {
        return $user->id === $luggage->user_id;
    }

    public function update(User $user, Luggage $luggage): bool
    {
        return $user->id === $luggage->user_id && $luggage->status->isModifiable();
    }

    public function delete(User $user, Luggage $luggage): bool
    {
        return $user->id === $luggage->user_id && $luggage->status->isReservable();
    }
}
