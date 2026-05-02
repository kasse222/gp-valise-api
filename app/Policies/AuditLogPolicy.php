<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Accès global admin uniquement
     */
    public function before(User $user): bool|null
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Création interdite via HTTP
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Lecture autorisée (déjà filtrée par before)
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        return true;
    }

    /**
     * Liste autorisée (déjà filtrée par before)
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Modification interdite
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * Suppression interdite
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
