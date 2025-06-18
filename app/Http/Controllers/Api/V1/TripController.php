<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Trip\StoreTripRequest;
use App\Http\Requests\Trip\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Status\TripTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TripController extends Controller
{
    /**
     * 📦 Lister les trajets de l’utilisateur connecté.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $trips = $user->trips()->latest()->paginate(10);

        return TripResource::collection($trips);
    }

    /**
     * 🛫 Créer un nouveau trajet.
     */
    public function store(StoreTripRequest $request)
    {
        $trip = Trip::create([
            'user_id'       => $request->user()->id,
            'departure'     => $request->departure,
            'destination'   => $request->destination,
            'date'          => $request->date,
            'capacity'      => $request->capacity,
            'flight_number' => $request->flight_number,
            'type_trip'     => $request->type_trip ?? TripTypeEnum::STANDARD->value,
            'status'        => $request->status ?? 'actif',
        ]);

        return response()->json([
            'message' => 'Trajet créé avec succès.',
            'trip'    => new TripResource($trip),
        ], 201);
    }

    /**
     * 🔍 Voir un trajet en détail (authentifié + propriétaire).
     */
    public function show($id)
    {
        $trip = Trip::findOrFail($id);

        $this->authorize('view', $trip);

        return new TripResource($trip);
    }

    /**
     * ✏️ Mettre à jour un trajet (propriétaire uniquement).
     */
    public function update(UpdateTripRequest $request, $id)
    {
        $trip = Trip::findOrFail($id);

        $this->authorize('update', $trip);

        $trip->update($request->validated());

        return new TripResource($trip);
    }

    /**
     * ❌ Supprimer un trajet (propriétaire uniquement).
     */
    public function destroy($id)
    {
        $trip = Trip::findOrFail($id);

        $this->authorize('delete', $trip);

        $trip->delete();

        return response()->json([
            'message' => 'Trajet supprimé avec succès.'
        ]);
    }
}
