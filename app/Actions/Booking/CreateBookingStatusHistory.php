<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Enums\BookingStatusEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateBookingStatusHistory
{
    /**
     * Crée un historique de statut de réservation.
     *
     * @param Booking $booking
     * @param array{old_status: BookingStatusEnum, new_status: BookingStatusEnum, reason?: string} $data
     */
    public static function execute(Booking $booking, array $data): BookingStatusHistory
    {
        // 1️⃣ Protection anti-doublon inutile
        if ($booking->status === $data['new_status']) {
            throw ValidationException::withMessages([
                'new_status' => 'Le statut est déjà défini comme tel.',
            ]);
        }

        // 2️⃣ Vérifie que la transition est autorisée
        if (! $data['old_status']->canTransitionTo($data['new_status'])) {
            throw ValidationException::withMessages([
                'new_status' => "Transition non autorisée de {$data['old_status']->label()} vers {$data['new_status']->label()}",
            ]);
        }

        // 3️⃣ Ajoute l’auteur si non précisé
        $data['user_id'] ??= Auth::id();

        // 4️⃣ Enregistrement en base
        return $booking->statusHistories()->create([
            'old_status' => $data['old_status'],
            'new_status' => $data['new_status'],
            'reason'     => $data['reason'] ?? 'Mise à jour manuelle du statut',
            'user_id'    => $data['user_id'],
        ]);
    }
}
