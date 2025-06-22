<?php

namespace App\Policies;

use App\Models\Luggage;
use App\Models\User;

class LuggagePolicy
{
    public function view(User $user, Luggage $luggage): bool
    {
        return $user->id === $luggage->user_id || $user->isAdmin();
    }

    public function update(User $user, Luggage $luggage): bool
    {
        return $user->id === $luggage->user_id;
    }

    public function delete(User $user, Luggage $luggage): bool
    {
        return $user->id === $luggage->user_id;
    }

    public function cancel(User $user, Luggage $luggage): bool
    {
        return $user->id === $luggage->user_id;
    }

    public function load(User $user, Luggage $luggage): bool
    {
        return $luggage->booking && $user->id === $luggage->booking->trip->user_id;
    }
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
