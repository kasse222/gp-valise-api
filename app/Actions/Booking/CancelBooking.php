<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CancelBooking
{
    public function execute(int $bookingId): Booking
    {
        $user = Auth::user();
        $booking = Booking::with(['bookingItems.luggage', 'trip'])->findOrFail($bookingId);

        // Vérification d'autorisation : voyageur ou expéditeur
        $isVoyageur = $booking->trip->user_id === $user->id;
        $isExpediteur = $booking->user_id === $user->id;

        if (!$isVoyageur && !$isExpediteur) {
            throw ValidationException::withMessages([
                'booking' => 'Vous n’êtes pas autorisé à annuler cette réservation.',
            ]);
        }

        // Déjà annulée ?
        if ($booking->status === 'annulee') {
            throw ValidationException::withMessages([
                'booking' => 'La réservation est déjà annulée.',
            ]);
        }

        // Libérer les valises
        foreach ($booking->bookingItems as $item) {
            $item->luggage->update(['status' => 'en_attente']);
            $item->delete();
        }

        $booking->update([
            'status' => 'annulee',
        ]);

        return $booking;
    }
}
