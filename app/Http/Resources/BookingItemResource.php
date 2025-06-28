<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'booking_id'   => $this->booking_id,
            'luggage_id'   => $this->luggage_id,
            'trip_id'      => $this->trip_id,

            'kg_reserved'  => round($this->kg_reserved, 2),
            'price'        => round($this->price, 2),

            // 🔗 Relations optionnelles (lazy loaded ou with('...'))
            'luggage'      => new LuggageResource($this->whenLoaded('luggage')),
            'trip'         => new TripResource($this->whenLoaded('trip')),

            // 🕓 Dates formatées avec fallback
            'created_at'   => optional($this->created_at)?->toDateTimeString(),
            'updated_at'   => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}
