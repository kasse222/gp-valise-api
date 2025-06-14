<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transforme une réservation (Booking) en tableau JSON.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'trip_id'      => $this->trip_id,
            'status'       => $this->status,
            'created_at'   => $this->created_at?->toDateTimeString(),
            'updated_at'   => $this->updated_at?->toDateTimeString(),

            // 💼 Items réservés (valises associées)
            'booking_items' => BookingItemResource::collection($this->whenLoaded('bookingItems')),
        ];
    }
}
