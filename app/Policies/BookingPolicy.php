<?php

namespace App\Policies;

use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $this->isSender($user, $booking)
            || $this->isTraveler($user, $booking)
            || $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRoleEnum::SENDER,
            UserRoleEnum::ADMIN,
        ], true);
    }

    public function update(User $user, Booking $booking): bool
    {
        return $this->isSender($user, $booking)
            || $this->isTraveler($user, $booking)
            || $this->isAdmin($user);
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $this->isSender($user, $booking)
            || $this->isAdmin($user);
    }

    public function confirm(User $user, Booking $booking): bool
    {
        return $this->isTraveler($user, $booking)
            || $this->isAdmin($user);
    }

    public function approve(User $user, Booking $booking): bool
    {
        return $this->isTraveler($user, $booking)
            || $this->isAdmin($user);
    }

    public function decline(User $user, Booking $booking): bool
    {
        return $this->isTraveler($user, $booking)
            || $this->isAdmin($user);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $this->isSender($user, $booking)
            || $this->isTraveler($user, $booking)
            || $this->isAdmin($user);
    }

    public function complete(User $user, Booking $booking): bool
    {
        return $this->isTraveler($user, $booking)
            || $this->isAdmin($user);
    }

    private function isSender(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id;
    }

    private function isTraveler(User $user, Booking $booking): bool
    {
        return $booking->trip !== null
            && $user->id === $booking->trip->user_id;
    }

    private function isAdmin(User $user): bool
    {
        return $user->role === UserRoleEnum::ADMIN;
    }
}
