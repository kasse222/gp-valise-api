<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,

            'departure'     => $this->departure,
            'destination'   => $this->destination,
            'date'          => optional($this->date)?->toDateString(),
            'flight_number' => $this->flight_number,
            'capacity'      => $this->capacity,      // grammes
            'price_per_kg'  => $this->price_per_kg,  // ← supprimé round() — integer

            'type_trip'  => $this->type_trip?->value,
            'type_badge' => $this->type_trip?->badge(),

            'status' => [
                'code'  => $this->status?->value,
                'label' => $this->status?->label(),
                'color' => $this->status?->color(),
            ],

            'is_reservable' => $this->isReservable(),
            'grams_disponible' => $this->whenLoaded('bookings', function () {
                return $this->capacity - $this->bookings->flatMap->bookingItems->sum('kg_reserved');
            }, $this->gramsDisponible()), // ← kgDisponible → gramsDisponible

            'user'      => new UserResource($this->whenLoaded('user')),
            'bookings'  => BookingResource::collection($this->whenLoaded('bookings')),
            'locations' => LocationResource::collection($this->whenLoaded('locations')),

            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}
