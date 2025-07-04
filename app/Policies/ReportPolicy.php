<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * Seul l'auteur ou un admin peut voir un report.
     */
    public function view(User $user, Report $report): bool
    {
        return $report->user_id === $user->id || $user->isAdmin(); // optionnel pour admin
    }


    /**
     * Seul l’auteur peut supprimer un report.
     * Optionnel : un modérateur peut aussi ?
     */
    public function delete(User $user, Report $report): bool
    {
        return $user->id === $report->user_id || $user->isAdmin();
    }

    /**
     * Tout utilisateur connecté peut créer un report.
     */
    public function create(User $user): bool
    {
        return $user->exists(); // Authentifié
    }

    /**
     * Seul un admin/modérateur peut valider ou traiter un report.
     */
    public function moderate(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }

    /**
     * Règle d’or : l’admin peut tout.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
