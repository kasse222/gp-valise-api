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
     * @param array{old_status: int|string|BookingStatusEnum, new_status: int|string|BookingStatusEnum, reason?: string} $data
     */
    public static function execute(Booking $booking, array $data): BookingStatusHistory
    {
        // 🔁 Cast explicite si int/string
        $old = $data['old_status'] instanceof BookingStatusEnum
            ? $data['old_status']
            : BookingStatusEnum::from($data['old_status']);

        $new = $data['new_status'] instanceof BookingStatusEnum
            ? $data['new_status']
            : BookingStatusEnum::from($data['new_status']);

        // 1️⃣ Doublon inutile
        if ($booking->status === $new) {
            throw ValidationException::withMessages([
                'new_status' => 'Le statut est déjà défini comme tel.',
            ]);
        }

        // 2️⃣ Transition interdite
        if (! $old->canTransitionTo($new)) {
            throw ValidationException::withMessages([
                'new_status' => "Transition non autorisée de {$old->label()} vers {$new->label()}",
            ]);
        }

        // 3️⃣ Auteur auto
        $changedBy = $data['changed_by'] ?? $data['user_id'] ?? Auth::id();

        // 4️⃣ Persistance
        return $booking->statusHistories()->create([
            'old_status'  => $old,
            'new_status'  => $new,
            'reason'      => $data['reason'] ?? 'Mise à jour manuelle du statut',
            'changed_by'  => $changedBy,
        ]);
    }
}
