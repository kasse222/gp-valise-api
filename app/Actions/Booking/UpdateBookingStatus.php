<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\User;

class UpdateBookingStatus
{
    public static function execute(Booking $booking, BookingStatusEnum $newStatus, User $user): Booking
    {
        if (! $booking->canBeUpdatedTo($newStatus, $user)) {
            abort(403, 'Transition de statut non autorisée.');
        }

        $booking->update(['status' => $newStatus]);

        return $booking;
    }
}
