<?php

namespace App\Policies;

use App\Models\BookingStatusHistory;
use App\Models\User;

class BookingStatusHistoryPolicy
{
    public function view(User $user, BookingStatusHistory $history): bool
    {
        return $user->id === $history->booking->user_id || $user->id === $history->booking->trip->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }

    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
