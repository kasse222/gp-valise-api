<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{

    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Location $location): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTrusted();
    }


    public function update(User $user, Location $location): bool
    {
        return $user->id === $location->trip->user_id
            || $user->isTrusted(); // 
    }


    public function delete(User $user, Location $location): bool
    {
        return $user->id === $location->trip->user_id;
    }
}
