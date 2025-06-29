<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\BookingStatusHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateBookingStatusHistory
{
    public static function execute(Booking $booking, array $data): BookingStatusHistory
    {
        // 🧠 1. Règle métier (optionnelle) : empêcher doublons ?
        if ($booking->status === $data['new_status']) {
            throw ValidationException::withMessages([
                'new_status' => 'Le statut est déjà défini comme tel.',
            ]);
        }

        // 🧠 2. Règle métier (optionnelle) : rôle auteur ?
        $data['user_id'] = Auth::id(); // ou injecte si tu préfères

        // ✅ 3. Enregistrement
        return $booking->statusHistories()->create($data);
    }
}
