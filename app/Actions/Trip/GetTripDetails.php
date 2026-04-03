<?php

namespace App\Actions\Trip;

use App\Models\Trip;

class GetTripDetails
{
    public function execute(Trip $trip): Trip
    {
        return $trip->load(['user']);
    }
}
