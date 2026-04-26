<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;

class PlanPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Plan $plan): bool
    {

        return $plan->is_active || $user->isAdmin();
    }

    public function create(User $user): bool
    {

        return $user->isAdmin();
    }

    public function update(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }

    public function manage(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }

    public function subscribe(User $user, Plan $plan): bool
    {

        return $user->isVerified();
    }

    public function upgrade(User $user): bool
    {

        return $user->isVerified();
    }
}
