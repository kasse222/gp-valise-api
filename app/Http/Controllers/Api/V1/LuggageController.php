<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Luggage\CreateLuggage;
use App\Actions\Luggage\UpdateLuggage;
use Illuminate\Routing\Controller;
use App\Http\Requests\Luggage\StoreLuggageRequest;
use App\Http\Requests\Luggage\UpdateLuggageRequest;
use App\Http\Resources\LuggageResource;
use App\Models\Luggage;
use App\Enums\LuggageStatusEnum;
use Illuminate\Http\Request;

class LuggageController extends Controller
{
    /**
     * 📦 Lister les valises de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        $luggages = $request->user()->luggages()->latest()->paginate(10);

        return LuggageResource::collection($luggages);
    }

    /**
     * ➕ Créer une nouvelle valise
     */
    public function store(StoreLuggageRequest $request)
    {
        $luggage = CreateLuggage::execute($request->user(), $request->validated());

        return response()->json(new LuggageResource($luggage), 201);
    }

    /**
     * 👁️ Voir une valise en détail
     */
    public function show(Luggage $luggage)
    {
        $this->authorize('view', $luggage);

        return new LuggageResource($luggage);
    }

    /**
     * ✏️ Modifier une valise
     */
    public function update(UpdateLuggageRequest $request, Luggage $luggage)
    {
        $this->authorize('update', $luggage);

        $luggage = UpdateLuggage::execute($luggage, $request->validated());

        return new LuggageResource($luggage);
    }

    /**
     * 🗑️ Supprimer une valise
     */
    public function destroy(Luggage $luggage)
    {
        $this->authorize('delete', $luggage);

        $luggage->delete();

        return response()->json([
            'message' => 'Valise supprimée avec succès.',
        ]);
    }
}
