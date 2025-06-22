<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function view(User $user, Location $location): bool
    {
        return true; // accessible à tous si c’est de la géo publique
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTrusted();
    }

    public function delete(User $user, Location $location): bool
    {
        return $user->isAdmin();
    }

    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
