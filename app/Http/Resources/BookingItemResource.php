<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'luggage_id'  => $this->luggage_id,
            'kg_reserved' => $this->kg_reserved,
            'price'       => $this->price,

            // Optionnel : si la relation est chargée
            'luggage'     => new LuggageResource($this->whenLoaded('luggage')),
        ];
    }
}
