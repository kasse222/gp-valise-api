<?php

namespace App\Policies;


use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isVerified();
    }

    public function refund(User $user, Transaction $transaction): bool
    {
        return $user->isAdmin() || $user->id === $transaction->user_id && $transaction->canBeRefunded();
    }

    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
