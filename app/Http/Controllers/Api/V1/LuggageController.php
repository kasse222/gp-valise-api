<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Http\Requests\Luggage\StoreLuggageRequest;
use App\Http\Requests\Luggage\UpdateLuggageRequest;
use App\Http\Resources\LuggageResource;
use App\Models\Luggage;
use Illuminate\Http\Request;

class LuggageController extends Controller
{
    public function index(Request $request)
    {
        $luggages = $request->user()->luggages()->latest()->paginate(10);
        return LuggageResource::collection($luggages);
    }

    public function store(StoreLuggageRequest $request)
    {
        $luggage = $request->user()->luggages()->create([
            ...$request->validated(),
            'status' => 'en_attente',
        ]);

        return response()->json(new LuggageResource($luggage), 201);
    }

    public function show(Luggage $luggage)
    {
        $luggage = Luggage::findOrFail($id);
        $this->authorize('view', $luggage);

        return new LuggageResource($luggage);
    }

    public function update(UpdateLuggageRequest $request, Luggage $luggage)
    {
        $this->authorize('update', $luggage);

        $luggage->update($request->validated());

        return new LuggageResource($luggage);
    }

    public function destroy(Luggage $luggage)
    {
        $this->authorize('delete', $luggage);

        $luggage->delete();

        return response()->json(['message' => 'Valise supprimée avec succès.']);
    }
}
