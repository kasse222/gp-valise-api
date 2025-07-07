<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    /**
     * 🔒 Priorité absolue pour les admins
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * 📍 Peut consulter un point géographique
     */
    public function view(User $user, Location $location): bool
    {
        return true; // Accès libre (si données publiques)
    }

    /**
     * ➕ Peut créer un point
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTrusted(); // Ou un rôle spécifique si besoin
    }

    /**
     * ✏️ Peut mettre à jour un point
     */
    public function update(User $user, Location $location): bool
    {
        return $user->id === $location->trip->user_id
            || $user->isTrusted(); // Le créateur du trip ou un rôle validé
    }

    /**
     * ❌ Peut supprimer un point
     */
    public function delete(User $user, Location $location): bool
    {
        return $user->id === $location->trip->user_id;
    }
}
