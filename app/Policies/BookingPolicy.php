<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return true; // tout utilisateur connecté
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id;
    }

    public function confirm(User $user, Booking $booking): bool
    {
        return $user->id === $booking->trip->user_id;
    }

    public function complete(User $user, Booking $booking): bool
    {
        return $user->id === $booking->trip->user_id;
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id || $user->isAdmin();
    }

    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
