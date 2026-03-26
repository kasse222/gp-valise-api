<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransactionPolicy
{
    use HandlesAuthorization;
    /**
     * ✅ Override global pour les admins
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * 📄 Autorise la vue de la liste (toutes les transactions de l'utilisateur connecté).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * 🔍 Autorise la vue d’une transaction uniquement si elle appartient à l’utilisateur.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        return $transaction->booking !== null
            && $transaction->booking->user_id === $user->id;
    }


    /**
     * ➕ Autorise la création de transaction si utilisateur vérifié.
     */
    public function create(User $user): bool
    {
        return $user->verified_user === true;
    }

    /**
     * 💸 Autorise le remboursement si transaction liée à un booking appartenant à l’utilisateur.
     */
    public function refund(User $user, Transaction $transaction): bool
    {
        return $transaction->booking !== null
            && $transaction->booking->user_id === $user->id;
    }
}
