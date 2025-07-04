<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;

class PlanPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        // 🛡 Accès total si admin
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Plan $plan): bool
    {
        // 📄 Les plans actifs sont publics, les autres réservés à l'admin
        return $plan->is_active || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        // 🚧 Création réservée aux admins
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
        // 👤 Seuls les utilisateurs vérifiés peuvent souscrire à un plan
        return $user->isVerified();
    }

    public function upgrade(User $user): bool
    {
        // ⚙️ Seuls les utilisateurs connectés et vérifiés peuvent upgrader
        return $user->isVerified();
    }
}
