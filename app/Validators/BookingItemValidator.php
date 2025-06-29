<?php

namespace App\Validators;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use Illuminate\Validation\ValidationException;

class BookingItemValidator
{
    public function validate(Booking $booking, array $data): void
    {
        $luggage = Luggage::findOrFail($data['luggage_id']);

        // Vérifie que la valise appartient bien à l’utilisateur
        if ($luggage->user_id !== $booking->user_id) {
            throw ValidationException::withMessages([
                'luggage_id' => 'Cette valise ne vous appartient pas.',
            ]);
        }

        // Vérifie qu'elle n'est pas déjà associée à un autre item
        if ($booking->bookingItems()->where('luggage_id', $luggage->id)->exists()) {
            throw ValidationException::withMessages([
                'luggage_id' => 'Cette valise est déjà utilisée pour cette réservation.',
            ]);
        }

        // Vérifie que le poids réservé est cohérent
        if ($data['kg_reserved'] <= 0) {
            throw ValidationException::withMessages([
                'kg_reserved' => 'Le poids réservé doit être supérieur à zéro.',
            ]);
        }

        // Autres règles potentielles...
    }

    public function validateUpdate(BookingItem $item, array $data): void
    {
        // Exemple : on interdit de modifier une valise déjà livrée
        if ($item->booking->status->isFinal()) {
            throw ValidationException::withMessages([
                'booking_item' => 'Impossible de modifier un élément d’une réservation finalisée.',
            ]);
        }

        if (isset($data['kg_reserved']) && $data['kg_reserved'] <= 0) {
            throw ValidationException::withMessages([
                'kg_reserved' => 'Le poids réservé doit être supérieur à zéro.',
            ]);
        }

        // Tu peux étendre ici selon les règles métier spécifiques à l’update
    }
}
