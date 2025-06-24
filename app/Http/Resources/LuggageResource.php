<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LuggageResource extends JsonResource
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
            'tracking_id'    => $this->tracking_id,

            // Dimensions
            'weight_kg'      => (float) $this->weight_kg,
            'length_cm'      => $this->length_cm,
            'width_cm'       => $this->width_cm,
            'height_cm'      => $this->height_cm,

            // Lieux & dates
            'pickup_city'    => $this->pickup_city,
            'delivery_city'  => $this->delivery_city,
            'pickup_date'    => optional($this->pickup_date)->toDateString(),
            'delivery_date'  => optional($this->delivery_date)->toDateString(),

            // Statut métier enrichi
            'status'         => $this->status->value,
            'status_label'   => $this->status->label(),
            'status_color'   => $this->status->color(),

            // Métadonnées
            'description'    => $this->description,

            // Relations
            'user'           => new UserResource($this->whenLoaded('user')),

            'created_at'     => $this->created_at->toDateTimeString(),
            'updated_at'     => $this->updated_at->toDateTimeString(),
        ];
    }
}
