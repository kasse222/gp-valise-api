<?php

namespace App\Actions\Booking;

use App\Actions\Booking\CreateBookingStatusHistory;
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

        $oldStatus = $booking->status;

        // 👇 enregistrer d'abord l'historique AVANT de changer le statut
        CreateBookingStatusHistory::execute($booking, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_id'    => $user->id,
        ]);

        $booking->update(['status' => $newStatus]);

        return $booking;
    }
}
