<?php

namespace App\Policies;


use App\Models\Plan;
use App\Models\User;

class PlanPolicy
{
    public function subscribe(User $user, Plan $plan): bool
    {
        return $user->isVerified();
    }

    public function manage(User $user, Plan $plan): bool
    {
        return $user->isAdmin(); // Gestion des plans par back-office
    }

    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
