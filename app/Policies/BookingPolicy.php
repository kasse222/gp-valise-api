<?php

namespace App\Policies;

use App\Enums\BookingStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id
            || $user->id === $booking->trip->user_id
            || $user->role === UserRoleEnum::ADMIN;
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
        return $booking->canBeUpdatedTo(
            BookingStatusEnum::from(request()->input('status')),
            $user
        );
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id
            && ! $booking->isFinal();
    }

    public function confirm(User $user, Booking $booking): bool
    {
        return $user->id === $booking->trip->user_id
            && $booking->status === BookingStatusEnum::EN_PAIEMENT
            && $booking->canBeConfirmed();
    }

    public function cancel(User $user, Booking $booking): bool
    {
        $isSender = $user->id === $booking->user_id;
        $isTraveler = $user->id === $booking->trip->user_id;

        if (! $booking->canBeCancelled()) {
            return false;
        }

        return match ($booking->status) {
            BookingStatusEnum::EN_PAIEMENT,
            BookingStatusEnum::PAIEMENT_ECHOUE => $isSender,

            BookingStatusEnum::ACCEPTE => $isSender || $isTraveler,

            default => false,
        };
    }

    public function complete(User $user, Booking $booking): bool
    {
        return $user->id === $booking->trip->user_id
            && $booking->status === BookingStatusEnum::CONFIRMEE
            && $booking->canBeDelivered();
    }
}
