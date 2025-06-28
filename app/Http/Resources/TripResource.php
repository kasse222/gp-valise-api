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
            'date'           => optional($this->date)?->toDateString(),
            'flight_number'  => $this->flight_number,
            'capacity'       => $this->capacity,
            'price_per_kg'   => round($this->price_per_kg, 2),

            // 🎯 Type enrichi
            'type_trip'      => $this->type_trip?->value,
            'type_badge'     => $this->type_trip?->badge(),

            // 📊 Statut enrichi
            'status' => [
                'code'     => $this->status?->value,
                'label'    => $this->status?->label(),
                'color'    => $this->status?->color(),
            ],

            // 📦 Capacité
            'is_reservable'  => $this->isReservable(),
            'kg_disponible'  => $this->whenLoaded('bookings', function () {
                return $this->capacity - $this->bookings->flatMap->bookingItems->sum('kg_reserved');
            }, $this->kgDisponible()),

            // 🔗 Relations (si chargées)
            'user'           => new UserResource($this->whenLoaded('user')),
            'bookings'       => BookingResource::collection($this->whenLoaded('bookings')),
            'locations'      => LocationResource::collection($this->whenLoaded('locations')),

            // 🕓 Timestamps
            'created_at'     => optional($this->created_at)?->toDateTimeString(),
            'updated_at'     => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}
