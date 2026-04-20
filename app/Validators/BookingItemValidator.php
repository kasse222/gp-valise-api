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

        if ($booking->isFinal()) {
            throw ValidationException::withMessages([
                'booking_item' => 'Impossible d’ajouter un élément à une réservation finalisée.',
            ]);
        }

        if ($luggage->user_id !== $booking->user_id) {
            throw ValidationException::withMessages([
                'luggage_id' => 'Cette valise ne vous appartient pas.',
            ]);
        }

        if ($booking->bookingItems()->where('luggage_id', $luggage->id)->exists()) {
            throw ValidationException::withMessages([
                'luggage_id' => 'Cette valise est déjà utilisée pour cette réservation.',
            ]);
        }

        if (isset($data['kg_reserved']) && $data['kg_reserved'] <= 0) {
            throw ValidationException::withMessages([
                'kg_reserved' => 'Le poids réservé doit être supérieur à zéro.',
            ]);
        }
    }

    public function validateUpdate(BookingItem $item, array $data): void
    {
        $item->loadMissing('booking');

        if (! $item->booking) {
            throw ValidationException::withMessages([
                'booking_item' => 'Impossible de modifier un élément sans réservation associée.',
            ]);
        }

        if ($item->booking->isFinal()) {
            throw ValidationException::withMessages([
                'booking_item' => 'Impossible de modifier un élément d’une réservation finalisée.',
            ]);
        }

        if (
            array_key_exists('booking_id', $data) ||
            array_key_exists('trip_id', $data) ||
            array_key_exists('luggage_id', $data)
        ) {
            throw ValidationException::withMessages([
                'booking_item' => 'Le rattachement du booking item ne peut pas être modifié.',
            ]);
        }

        if (isset($data['kg_reserved']) && $data['kg_reserved'] <= 0) {
            throw ValidationException::withMessages([
                'kg_reserved' => 'Le poids réservé doit être supérieur à zéro.',
            ]);
        }

        if (isset($data['price']) && $data['price'] < 0) {
            throw ValidationException::withMessages([
                'price' => 'Le prix ne peut pas être négatif.',
            ]);
        }
    }
}
