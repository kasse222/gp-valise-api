<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'user_id'        => $this->user_id,

            // 🛫 Infos trajet
            'departure'      => $this->departure,
            'destination'    => $this->destination,
            'date'           => $this->date?->toDateString(),
            'flight_number'  => $this->flight_number,
            'capacity'       => $this->capacity,

            // 🎯 Type enrichi (enum)
            'type_trip'      => $this->type_trip?->value,
            'type_badge'     => $this->type_trip?->badge(), // contient label + color

            // 📦 Disponibilité & état métier
            'is_reservable'  => $this->isReservable(),
            'kg_disponible'  => $this->kgDisponible(),

            // 🔗 Relations
            'user'           => new UserResource($this->whenLoaded('user')),
            'bookings'       => BookingResource::collection($this->whenLoaded('bookings')),
            'locations'      => LocationResource::collection($this->whenLoaded('locations')),

            // 🕓 Dates
            'created_at'     => $this->created_at->toDateTimeString(),
            'updated_at'     => $this->updated_at->toDateTimeString(),
        ];
    }
}
