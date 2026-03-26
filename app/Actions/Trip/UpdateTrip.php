<?php

namespace App\Actions\Trip;

use App\Models\Trip;

class UpdateTrip
{
    public function execute(Trip $trip, array $validated): Trip
    {
        $trip->update($validated);

        return $trip->fresh();
    }
}
