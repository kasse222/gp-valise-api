<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CompleteBooking
{
    public function execute(int $bookingId): Booking
    {
        $user = Auth::user();
        $booking = Booking::with('trip')->findOrFail($bookingId);

        // 🔐 Vérifie que l’utilisateur est bien le voyageur
        if ($booking->trip->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'booking' => 'Vous n’êtes pas autorisé à finaliser cette livraison.',
            ]);
        }

        // ❌ Doit être confirmée avant d’être livrée
        if ($booking->status !== 'confirmee') {
            throw ValidationException::withMessages([
                'booking' => 'La réservation doit être confirmée avant d’être marquée comme livrée.',
            ]);
        }

        // ✅ Mise à jour du statut
        $booking->update([
            'status' => 'livree',
        ]);

        return $booking;
    }
}
