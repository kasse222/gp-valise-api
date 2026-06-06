<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\BookingStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickupLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $booking = $this->booking;
        $revealed = $this->isRevealedFor($booking);

        return [
            'id'      => $this->id,
            'city'    => $this->city,

            // Coordonnées approximatives — toujours visibles
            'approximate_latitude'  => $this->approximate_latitude,
            'approximate_longitude' => $this->approximate_longitude,

            // Coordonnées exactes + adresse — uniquement si booking confirmé
            'latitude'     => $revealed ? $this->latitude : null,
            'longitude'    => $revealed ? $this->longitude : null,
            'address'      => $revealed ? $this->address : null,
            'instructions' => $revealed ? $this->instructions : null,

            'revealed' => $revealed,
        ];
    }
}
