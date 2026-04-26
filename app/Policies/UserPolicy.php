<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{

    public function view(User $user, User $target): bool
    {
        return $user->id === $target->id || $user->isAdmin();
    }


    public function update(User $user, User $target): bool
    {
        return $user->id === $target->id || $user->isAdmin();
    }


    public function delete(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->id !== $target->id;
    }


    public function viewKyc(User $user, User $target): bool
    {
        return $user->id === $target->id || $user->isAdmin();
    }


    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
