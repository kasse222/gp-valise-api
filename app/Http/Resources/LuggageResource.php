<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LuggageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'tracking_id' => $this->tracking_id,

            'weight_kg' => round($this->weight_kg, 2),
            'length_cm' => round($this->length_cm, 2),
            'width_cm'  => round($this->width_cm, 2),
            'height_cm' => round($this->height_cm, 2),

            'pickup_city'   => $this->pickup_city,
            'delivery_city' => $this->delivery_city,
            'pickup_date'   => optional($this->pickup_date)?->toDateString(),
            'delivery_date' => optional($this->delivery_date)?->toDateString(),

            'status'       => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'is_final'     => $this->status->isFinal(),

            'category'       => $this->category?->value,
            'category_label' => $this->category?->label(),
            'category_icon'  => $this->category?->icon(),

            'is_fragile'          => $this->is_fragile,
            'insurance_requested' => $this->insurance_requested,

            'description'   => $this->description,
            'photo_path'    => $this->photo_path,
            'content_items' => $this->content_items ?? [],

            'user' => new UserResource($this->whenLoaded('user')),

            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
