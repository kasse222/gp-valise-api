<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // ou une logique : return $user->isAdmin();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id;
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }
    public function update(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id;
    }
    public function refund(User $user, Payment $payment): bool
    {
        return $user->isAdmin() || $user->id === $payment->booking?->user_id;
    }

    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
