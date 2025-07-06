<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Transaction;

class TransactionPolicy
{
    /**
     * Autorise un utilisateur à voir ses propres transactions.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->user_id;
    }
    public function viewAny(User $user): bool
    {
        return true; // ou une logique plus permissive : $user->isClient() || $user->isVoyageur()
    }

    /**
     * Autorise la création si l'utilisateur est vérifié.
     */
    public function create(User $user): bool
    {
        return $user->verified_user === true;
    }


    /**
     * Autorise le remboursement si remboursable et si propriétaire ou admin.
     */
    public function refund(User $user, Transaction $transaction): bool
    {
        return ($user->isAdmin() || $user->id === $transaction->user_id)
            && $transaction->canBeRefunded();
    }

    /**
     * Accès global pour les admins.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
