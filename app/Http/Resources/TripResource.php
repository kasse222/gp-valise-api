<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Actions\Trip\ResolvePickupVisibility;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();

        ['isOwner' => $isOwner, 'revealed' => $revealed] =
            ResolvePickupVisibility::handle($this->resource, $user);

        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'departure'     => $this->departure,
            'destination'   => $this->destination,
            'date'          => optional($this->date)?->toDateString(),
            'flight_number' => $this->flight_number,
            'capacity'      => $this->capacity,
            'price_per_kg'  => $this->price_per_kg,
            'currency'      => $this->currency?->value,

            'type_trip'  => $this->type_trip?->value,
            'type_badge' => $this->type_trip?->badge(),

            'status' => [
                'code'  => $this->status?->value,
                'label' => $this->status?->label(),
                'color' => $this->status?->color(),
            ],

            'is_reservable'    => $this->isReservable(),
            'grams_disponible' => $this->whenLoaded('bookings', function () {
                return $this->capacity - $this->bookings->flatMap->bookingItems->sum('kg_reserved');
            }, $this->gramsDisponible()),

            'user'      => new UserResource($this->whenLoaded('user')),
            'bookings'  => BookingResource::collection($this->whenLoaded('bookings')),
            'locations' => LocationResource::collection($this->whenLoaded('locations')),

            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),

            // Champs directs — propriétaire uniquement
            'pickup_address'        => $isOwner ? $this->pickup_address        : null,
            'pickup_city'           => $isOwner ? $this->pickup_city           : null,
            'pickup_instructions'   => $isOwner ? $this->pickup_instructions   : null,
            'delivery_address'      => $isOwner ? $this->delivery_address      : null,
            'delivery_city'         => $isOwner ? $this->delivery_city         : null,
            'delivery_instructions' => $isOwner ? $this->delivery_instructions : null,

            // Pickup location — révélé selon contexte
            'pickup_location' => $this->pickup_address ? [
                'address'               => ($revealed || $isOwner) ? $this->pickup_address      : null,
                'city'                  => $this->pickup_city,
                'latitude'              => ($revealed || $isOwner) ? $this->pickup_latitude     : null,
                'longitude'             => ($revealed || $isOwner) ? $this->pickup_longitude    : null,
                'approximate_latitude'  => $this->pickup_approx_latitude,
                'approximate_longitude' => $this->pickup_approx_longitude,
                'instructions'          => ($revealed || $isOwner) ? $this->pickup_instructions : null,
                'revealed'              => $revealed || $isOwner,
            ] : null,

            'delivery_location' => $this->delivery_address ? [
                'address'               => ($revealed || $isOwner) ? $this->delivery_address      : null,
                'city'                  => $this->delivery_city,
                'latitude'              => ($revealed || $isOwner) ? $this->delivery_latitude     : null,
                'longitude'             => ($revealed || $isOwner) ? $this->delivery_longitude    : null,
                'approximate_latitude'  => $this->delivery_approx_latitude,
                'approximate_longitude' => $this->delivery_approx_longitude,
                'instructions'          => ($revealed || $isOwner) ? $this->delivery_instructions : null,
                'revealed'              => $revealed || $isOwner,
            ] : null,
        ];
    }
}
