<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LuggageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'description'    => $this->description,
            'weight_kg'      => $this->weight_kg,
            'dimensions'     => $this->dimensions,
            'pickup_city'    => $this->pickup_city,
            'delivery_city'  => $this->delivery_city,
            'pickup_date'    => $this->pickup_date?->toDateString(),
            'delivery_date'  => $this->delivery_date?->toDateString(),
            'status'         => $this->status->value, // Enum string
            'created_at'     => $this->created_at?->toDateTimeString(),
            'updated_at'     => $this->updated_at?->toDateTimeString(),

            // Relations optionnelles
            'user'           => new UserResource($this->whenLoaded('user')),
            'booking_items'  => BookingItemResource::collection($this->whenLoaded('bookingItems')),
        ];
    }
}
