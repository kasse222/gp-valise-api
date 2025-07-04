<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Trip\CreateTrip;
use Illuminate\Routing\Controller;
use App\Http\Requests\Trip\StoreTripRequest;
use App\Http\Requests\Trip\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class TripController extends Controller
{
    use AuthorizesRequests;

    /**
     * 📦 Lister les trajets
     */
    public function index(Request $request)
    {
        $trips = Trip::with(['user'])->latest()->paginate(10);
        return TripResource::collection($trips);
    }

    /**
     * 🔍 Voir un trajet spécifique
     */
    public function show(Trip $trip)
    {
        return new TripResource($trip->load(['user']));
    }

    /**
     * ➕ Créer un trajet (voyageur)
     */
    public function store(StoreTripRequest $request)
    {
        $trip = CreateTrip::execute($request->user(), $request->validated());

        return response()->json(new TripResource($trip), 201);
    }

    /**
     * ✏️ Modifier un trajet
     */
    public function update(UpdateTripRequest $request, Trip $trip)
    {
        $this->authorize('update', $trip);

        $trip->update($request->validated());

        return new TripResource($trip);
    }

    /**
     * ❌ Supprimer un trajet
     */
    public function destroy(Trip $trip)
    {
        $this->authorize('delete', $trip);

        $trip->delete();

        return response()->json(['message' => 'Trajet supprimé avec succès.']);
    }
}
