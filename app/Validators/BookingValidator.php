<?php

declare(strict_types=1);

namespace App\Validators;

use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class BookingValidator
{
    public function validateReservation(User $user, Trip $trip, array $data): void
    {
        $this->validateUserOwnership($user, $trip);
        $this->validateItemsPresence($data);
        $this->validateLuggages($user, $data);
        $this->validateCapacity($trip, $data);
    }

    protected function validateUserOwnership(User $user, Trip $trip): void
    {
        if ((int) $user->id === (int) $trip->user_id) {
            throw ValidationException::withMessages([
                'user' => 'Le propriétaire du trajet ne peut pas réserver son propre trajet.',
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

    protected function validateLuggages(User $user, array $data): void
    {
        $luggageIds = collect($data['items'])
            ->pluck('luggage_id')
            ->filter()
            ->unique()
            ->values();

        $luggages = Luggage::query()
            ->whereIn('id', $luggageIds)
            ->get();

        if ($luggages->count() !== $luggageIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Une ou plusieurs valises sont introuvables.',
            ]);
        }

        foreach ($luggages as $luggage) {
            if ((int) $luggage->user_id !== (int) $user->id) {
                throw ValidationException::withMessages([
                    'items' => 'Une valise ne vous appartient pas.',
                ]);
            }

            if (! $luggage->isAvailable()) {
                throw ValidationException::withMessages([
                    'items' => "La valise {$luggage->id} n'est pas disponible.",
                ]);
            }
        }
    }

    protected function validateCapacity(Trip $trip, array $data): void
    {
        $requestedGrams = (int) collect($data['items'])->sum('kg_reserved'); // ← grammes

        if ($requestedGrams <= 0) {
            throw ValidationException::withMessages([
                'kg_reserved' => 'Le poids total doit être supérieur à zéro.',
            ]);
        }

        $remaining = $trip->gramsDisponible(); // ← grammes

        if ($requestedGrams > $remaining) {
            $remainingKg = round($remaining / 1000, 2);
            throw ValidationException::withMessages([
                'kg_reserved' => "Capacité insuffisante. Restant : {$remainingKg} kg.",
            ]);
        }
    }
}
