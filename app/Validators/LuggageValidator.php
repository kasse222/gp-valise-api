<?php

namespace App\Validators;

use App\Models\Luggage;
use App\Models\Trip;
use Illuminate\Validation\ValidationException;

class LuggageValidator
{
    public function validateReservation(Luggage $luggage, Trip $trip, float $kgReserved): void
    {
        if ($trip->isClosed()) {
            throw ValidationException::withMessages([
                'trip' => 'Ce trajet est clôturé.',
            ]);
        }

        if (! $trip->canAcceptKg($kgReserved)) {
            throw ValidationException::withMessages([
                'kg_reserved' => 'Le poids dépasse la capacité disponible du trajet.',
            ]);
        }

        if ($luggage->status !== 'disponible') {
            throw ValidationException::withMessages([
                'luggage' => 'Ce colis n’est pas disponible pour la réservation.',
            ]);
        }
    }
}
