<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{

    public function view(User $user, Report $report): bool
    {
        return $report->user_id === $user->id || $user->isAdmin(); // optionnel pour admin
    }



    public function delete(User $user, Report $report): bool
    {
        return $user->id === $report->user_id || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->exists(); // Authentifié
    }


    public function moderate(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }


    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
