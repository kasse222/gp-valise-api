<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    public function create(User $user): bool
    {
        return $user->isVerified(); // Optionnel : ou un rôle particulier
    }

    public function view(User $user, Invitation $invitation): bool
    {
        return $user->id === $invitation->sender_id || $user->id === $invitation->receiver_id;
    }

    public function delete(User $user, Invitation $invitation): bool
    {
        return $user->id === $invitation->sender_id || $user->isAdmin();
    }

    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
