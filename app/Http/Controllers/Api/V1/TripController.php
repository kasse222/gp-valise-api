<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Trip\CreateTrip;
use App\Actions\Trip\GetTripDetails;
use App\Actions\Trip\ListTrips;
use App\Actions\Trip\UpdateTrip;
use App\Http\Requests\Trip\StoreTripRequest;
use App\Http\Requests\Trip\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TripController extends Controller
{
    use AuthorizesRequests;


    public function index(ListTrips $action)
    {
        $trips = $action->execute();

        return TripResource::collection($trips);
    }


    public function show(Trip $trip, GetTripDetails $action)
    {
        $trip = $action->execute($trip);

        return new TripResource($trip);
    }

    public function store(StoreTripRequest $request, CreateTrip $action)
    {
        $trip = $action->execute($request->user(), $request->validated());

        return (new TripResource($trip))
            ->response()
            ->setStatusCode(201);
    }


    public function update(UpdateTripRequest $request, Trip $trip, UpdateTrip $action)
    {
        $this->authorize('update', $trip);

        $trip = $action->execute($trip, $request->validated());

        return new TripResource($trip);
    }


    public function destroy(Trip $trip)
    {
        $this->authorize('delete', $trip);

        $trip->delete();

        return response()->json(['message' => 'Trajet supprimé avec succès.']);
    }
}
