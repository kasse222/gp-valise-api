<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Autorise l'accès à son propre profil.
     */
    public function view(User $user, User $target): bool
    {
        return $user->id === $target->id || $user->isAdmin();
    }

    /**
     * Seul l'utilisateur concerné peut modifier son profil (ou admin).
     */
    public function update(User $user, User $target): bool
    {
        return $user->id === $target->id || $user->isAdmin();
    }

    /**
     * Seul un admin peut supprimer un autre utilisateur.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->id !== $target->id;
    }

    /**
     * Accès à la vérification KYC ?
     */
    public function viewKyc(User $user, User $target): bool
    {
        return $user->id === $target->id || $user->isAdmin();
    }

    /**
     * Accès accordé à tous les admins pour tout.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
