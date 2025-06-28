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

            // 📍 Coordonnées GPS
            'latitude'     => round($this->latitude, 6),
            'longitude'    => round($this->longitude, 6),

            // 🏙️ Infos localisation
            'city'         => $this->city,
            'order_index'  => $this->order_index,

            // 🧠 Enums enrichis
            'position'     => $this->position->value,
            'position_label' => $this->position->label(),
            'type'         => $this->type->value,
            'type_label'   => $this->type->label(),

            // 🔍 Drapeaux utiles
            'is_departure'       => $this->isDeparture(),
            'is_customs_point'   => $this->isCustomsCheckpoint(),
            'is_hub'             => $this->isHub(),

            // 📅 Timestamps
            'created_at'   => $this->created_at->toDateTimeString(),
        ];
    }
}
