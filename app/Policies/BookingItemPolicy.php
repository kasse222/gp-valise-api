<?php

namespace App\Policies;

use App\Models\BookingItem;
use App\Models\User;

class BookingItemPolicy
{
    public function view(User $user, BookingItem $item): bool
    {
        return $user->id === $item->booking->user_id || $user->id === $item->booking->trip->user_id;
    }

    public function update(User $user, BookingItem $item): bool
    {
        return $user->id === $item->booking->user_id && !$item->booking->status->isFinal();
    }

    public function delete(User $user, BookingItem $item): bool
    {
        return $user->id === $item->booking->user_id;
    }

    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
