<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRoleEnum;
use App\Models\Dispute;
use App\Models\User;

class DisputePolicy
{
    public function view(User $user, Dispute $dispute): bool
    {
        return $user->isAdmin()
            || $user->id === $dispute->booking->user_id
            || $user->id === $dispute->booking->trip->user_id;
    }

    public function addMessage(User $user, Dispute $dispute): bool
    {
        return $this->view($user, $dispute) && ! $dispute->isResolved();
    }
}
