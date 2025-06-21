<?php

namespace App\Services;

use App\Http\Requests\Trip\StoreTripRequest;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;

class TripService
{
    public function createFromRequest(StoreTripRequest $request): Trip
    {
        return Trip::create([
            'user_id'     => Auth::id(),
            'departure'   => $request->departure,
            'destination' => $request->destination,
            'date'        => $request->date,
            'capacity'    => $request->capacity,
            'type_trip'   => $request->type_trip,
            'flight_number' => $request->flight_number,
        ]);
    }
}
