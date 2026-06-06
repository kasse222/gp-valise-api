<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\PickupLocation\StorePickupLocationRequest;
use App\Http\Resources\PickupLocationResource;
use App\Models\Booking;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PickupLocationController extends Controller
{
    use AuthorizesRequests;

    public function show(Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $pickup = $booking->pickupLocation;

        if (! $pickup) {
            return response()->json([
                'message' => 'Aucun point de dépôt défini pour cette réservation.',
                'data'    => null,
            ]);
        }

        return response()->json([
            'data' => new PickupLocationResource($pickup),
        ]);
    }

    public function store(StorePickupLocationRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $pickup = $booking->pickupLocation()->updateOrCreate(
            ['booking_id' => $booking->id],
            $request->validated()
        );

        return response()->json([
            'message' => 'Point de dépôt enregistré.',
            'data'    => new PickupLocationResource($pickup->load('booking')),
        ], 201);
    }
}
