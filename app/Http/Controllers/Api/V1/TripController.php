<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Trip\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Http\Request;

class TripController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $trips = $user->trips()->latest()->paginate(10);

        return TripResource::collection($trips);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTripRequest $request)
    {
        $trip = Trip::create([
            'user_id'     => $request->user()->id,
            'departure'   => $request->departure,
            'destination' => $request->destination,
            'date'        => $request->date,
            'capacity'    => $request->capacity,
            'flight_number' => $request->flight_number,
            'status'      => $request->status ?? 'open',
        ]);

        return response()->json([
            'message' => 'Trajet créé avec succès.',
            'trip'    => new TripResource($trip),
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $trip = Trip::where('id', $id)
            ->where('user_id', auth()->id()) // Sécurité : ownership
            ->firstOrFail();

        return new TripResource($trip);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTripRequest $request, $id)
    {
        $trip = Trip::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $trip->update($request->validated());

        return new TripResource($trip);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $trip = Trip::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $trip->delete();    // soft delete automatique pas une suppreddion total

        return response()->json([
            'message' => 'Trajet supprimé avec succès.'
        ], 200);
    }
}
