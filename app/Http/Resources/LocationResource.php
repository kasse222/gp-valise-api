<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    /**
     * Transform the location resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'trip_id'      => $this->trip_id,

            // Coordonnées GPS
            'latitude'     => (float) $this->latitude,
            'longitude'    => (float) $this->longitude,

            // Infos de passage
            'city'         => $this->city,
            'order_index'  => $this->order_index,

            'created_at'   => $this->created_at->toDateTimeString(),
        ];
    }
}
