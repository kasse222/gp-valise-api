<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LuggageResource extends JsonResource
{
    /**
     * Transform the luggage resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'tracking_id'    => $this->tracking_id,

            // 📦 Dimensions & poids
            'weight_kg'      => round($this->weight_kg, 2),
            'length_cm'      => round($this->length_cm, 2),
            'width_cm'       => round($this->width_cm, 2),
            'height_cm'      => round($this->height_cm, 2),
            // 'volume_cm3'     => $this->volume_cm3,

            // 📍 Lieux & dates
            'pickup_city'    => $this->pickup_city,
            'delivery_city'  => $this->delivery_city,
            'pickup_date'    => optional($this->pickup_date)?->toDateString(),
            'delivery_date'  => optional($this->delivery_date)?->toDateString(),

            // 📌 Statut enrichi
            'status'         => $this->status->value,
            'status_label'   => $this->status->label(),
            'status_color'   => $this->status->color(),
            'is_final'       => $this->status->isFinal(),

            // 🔧 Options
            'is_fragile'     => $this->is_fragile,
            'insurance_requested' => $this->insurance_requested,

            // 📝 Métadonnées
            'description'    => $this->description,

            // 🔗 Relations
            'user'           => new UserResource($this->whenLoaded('user')),

            // 📅 Audit
            'created_at'     => $this->created_at->toDateTimeString(),
            'updated_at'     => $this->updated_at->toDateTimeString(),
        ];
    }
}
