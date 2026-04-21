<?php

namespace App\Validators;

use App\Models\Luggage;
use App\Models\Trip;
use Illuminate\Validation\ValidationException;

class BookingValidator
{
    public function validateReservation(Trip $trip, array $data): void
    {
        $this->validateUserOwnership($trip, $data);
        $this->validateItemsPresence($data);
        $this->validateLuggages($data, $trip);
        $this->validateCapacity($trip, $data);
    }

    protected function validateUserOwnership(Trip $trip, array $data): void
    {
        if (! isset($data['user_id'])) {
            throw ValidationException::withMessages([
                'user_id' => 'Utilisateur requis pour la réservation.',
            ]);
        }

        if ((int) $data['user_id'] === (int) $trip->user_id) {
            throw ValidationException::withMessages([
                'user_id' => 'Le propriétaire du trajet ne peut pas réserver son propre trajet.',
            ]);
        }
    }

    protected function validateItemsPresence(array $data): void
    {
        if (empty($data['items']) || ! is_array($data['items'])) {
            throw ValidationException::withMessages([
                'items' => 'Au moins un item est requis pour réserver.',
            ]);
        }
    }

    protected function validateLuggages(array $data, Trip $trip): void
    {
        $luggageIds = collect($data['items'])
            ->pluck('luggage_id')
            ->filter()
            ->unique();

        $luggages = Luggage::whereIn('id', $luggageIds)->get();

        if ($luggages->count() !== $luggageIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Une ou plusieurs valises sont introuvables.',
            ]);
        }

        foreach ($luggages as $luggage) {
            if ((int) $luggage->user_id !== (int) $data['user_id']) {
                throw ValidationException::withMessages([
                    'items' => 'Une valise ne vous appartient pas.',
                ]);
            }

            if (! $luggage->isAvailable()) {
                throw ValidationException::withMessages([
                    'items' => "La valise {$luggage->id} n’est pas disponible.",
                ]);
            }
        }
    }

    protected function validateCapacity(Trip $trip, array $data): void
    {
        $requestedKg = (float) collect($data['items'])->sum('kg_reserved');

        if ($requestedKg <= 0) {
            throw ValidationException::withMessages([
                'kg_reserved' => 'Le poids total doit être supérieur à zéro.',
            ]);
        }

        $remaining = $trip->kgDisponible();

        if ($requestedKg > $remaining) {
            throw ValidationException::withMessages([
                'kg_reserved' => "Capacité insuffisante. Restant : {$remaining} kg.",
            ]);
        }
    }

    public function validateCancel($booking): void
    {
        if (! $booking->status->canBeCancelled()) {
            throw ValidationException::withMessages([
                'booking' => 'Cette réservation ne peut pas être annulée.',
            ]);
        }
    }

    public function validateConfirm($booking): void
    {
        if (! $booking->status->canBeConfirmed()) {
            throw ValidationException::withMessages([
                'booking' => 'Cette réservation ne peut pas être confirmée.',
            ]);
        }
    }

    public function validateComplete($booking): void
    {
        if (! $booking->status->canBeDelivered()) {
            throw ValidationException::withMessages([
                'booking' => 'Cette réservation ne peut pas être marquée comme livrée.',
            ]);
        }
    }
}
