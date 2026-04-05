<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransactionPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $transaction->booking !== null
            && $transaction->booking->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->verified_user === true;
    }

    public function refund(User $user, Transaction $transaction): bool
    {
        return false;
    }
}
