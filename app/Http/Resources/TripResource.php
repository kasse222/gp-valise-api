<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'departure'   => $this->departure,
            'destination' => $this->destination,
            'date'        => $this->date,
            'capacity'    => $this->capacity,
            'flight_number' => $this->flight_number,
            'status'      => $this->status,
            'created_at'  => $this->created_at,
        ];
    }
}
