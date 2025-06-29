<?php

namespace App\Actions\Report;

use App\Models\Report;
use App\Models\User;

class CreateReport
{
    /**
     * Crée un signalement associé à l’utilisateur.
     */
    public static function execute(User $user, array $data): Report
    {
        return $user->reports()->create($data);
    }
}
